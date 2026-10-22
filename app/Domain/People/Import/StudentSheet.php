<?php

namespace App\Domain\People\Import;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\Relationship;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Provinces;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * One sheet: a student, their guardian and their class per row. Read by column position, so a school can
 * translate or reword the headings. Every row is checked against the school's data and the rows above it.
 */
final class StudentSheet
{
    public const COLUMNS = [
        'student_code', 'last_name_km', 'first_name_km', 'last_name_en', 'first_name_en', 'gender', 'dob',
        'birth_province', 'classroom', 'guardian_name', 'guardian_phone', 'relationship',
    ];

    public const MAX_ROWS = 5000;

    private const GENDERS = [
        'ប្រុស' => Gender::Male, 'ប' => Gender::Male, 'm' => Gender::Male, 'male' => Gender::Male,
        'ស្រី' => Gender::Female, 'ស' => Gender::Female, 'f' => Gender::Female, 'female' => Gender::Female,
    ];

    private const RELATIONSHIPS = [
        'ឪពុក' => Relationship::Father, 'father' => Relationship::Father,
        'ម្តាយ' => Relationship::Mother, 'ម្ដាយ' => Relationship::Mother, 'mother' => Relationship::Mother,
        'អាណាព្យាបាល' => Relationship::Guardian, 'guardian' => Relationship::Guardian,
    ];

    /** @var array<string, true> */
    private array $codes = [];

    /** @var array<string, true> name + date of birth */
    private array $people = [];

    /** @var array<string, true> phones of guardians who already have a name */
    private array $namedPhones = [];

    /** @var array<string, int> lower-case class name => id */
    private array $classrooms = [];

    public function __construct(private ?AcademicYear $year)
    {
        foreach (Student::withTrashed()->get(['student_code', 'last_name_km', 'first_name_km', 'dob']) as $student) {
            $this->codes[mb_strtolower($student->student_code)] = true;
            if ($student->dob) {
                $this->people[$this->personKey($student->last_name_km, $student->first_name_km, $student->dob->toDateString())] = true;
            }
        }
        foreach (Guardian::whereNotNull('phone')->pluck('phone') as $phone) {
            $this->namedPhones[$phone] = true;
        }
        if ($year) {
            $this->classrooms = Classroom::where('academic_year_id', $year->id)->pluck('id', 'name')
                ->mapWithKeys(fn (int $id, string $name) => [mb_strtolower($name) => $id])->all();
        }
    }

    /** @return list<string> */
    public static function headings(): array
    {
        return array_map(
            fn (string $column) => __("people.import.columns.{$column}", [], 'km').' / '.__("people.import.columns.{$column}", [], 'en'),
            self::COLUMNS,
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $cells  the sheet's rows, heading row first
     * @return list<array{line: int, data: array<string, mixed>, errors: list<string>}>
     */
    public function check(array $cells): array
    {
        $rows = [];
        foreach (array_slice($cells, 1, preserve_keys: true) as $index => $cellRow) {
            $values = array_map(fn ($cell) => $this->clean($cell), array_slice(array_values($cellRow), 0, count(self::COLUMNS)));
            $raw = array_combine(self::COLUMNS, array_pad($values, count(self::COLUMNS), null));

            if (array_filter($raw, fn ($value) => $value !== null) === []) {
                continue; // blank line
            }

            // Only the date may stay a number (an Excel date); a code or phone typed as a number is text.
            foreach ($raw as $column => $value) {
                if ($column !== 'dob' && $value !== null) {
                    $raw[$column] = (string) $value;
                }
            }

            $rows[] = $this->checkRow($index + 1, $raw);
        }

        return $rows;
    }

    /**
     * @param  array<string, ?string>  $raw
     * @return array{line: int, data: array<string, mixed>, errors: list<string>}
     */
    private function checkRow(int $line, array $raw): array
    {
        $errors = [];
        // Not an arrow function: those capture $errors by value, and every error would be lost.
        $error = function (string $key, array $replace = []) use (&$errors): void {
            $errors[] = __("people.import.errors.{$key}", $replace);
        };
        $label = fn (string $column) => __("people.import.columns.{$column}");

        foreach (['last_name_km', 'first_name_km', 'gender'] as $required) {
            if ($raw[$required] === null) {
                $error('required', ['column' => $label($required)]);
            }
        }
        foreach (['last_name_km', 'first_name_km', 'last_name_en', 'first_name_en', 'guardian_name'] as $column) {
            if (mb_strlen((string) $raw[$column]) > 100) {
                $error('too_long', ['column' => $label($column)]);
            }
        }

        $gender = $raw['gender'] === null ? null : (self::GENDERS[mb_strtolower($raw['gender'])] ?? null);
        if ($raw['gender'] !== null && ! $gender) {
            $error('gender', ['value' => $raw['gender']]);
        }

        $dob = $this->date($raw['dob']);
        if ($raw['dob'] !== null && ! $dob) {
            $error('dob', ['value' => $raw['dob']]);
        }

        $province = Provinces::find($raw['birth_province']);
        if ($raw['birth_province'] !== null && ! $province) {
            $error('province', ['value' => $raw['birth_province']]);
        }

        $code = $raw['student_code'];
        if ($code !== null) {
            if (mb_strlen($code) > 30) {
                $error('too_long', ['column' => $label('student_code')]);
            } elseif (isset($this->codes[mb_strtolower($code)])) {
                $error('code_taken', ['code' => $code]);
            }
            $this->codes[mb_strtolower($code)] = true;
        }

        if ($dob && $raw['last_name_km'] !== null && $raw['first_name_km'] !== null) {
            $person = $this->personKey($raw['last_name_km'], $raw['first_name_km'], $dob);
            if (isset($this->people[$person])) {
                $error('duplicate_student');
            }
            $this->people[$person] = true;
        }

        $classroomId = null;
        if ($raw['classroom'] !== null) {
            $classroomId = $this->classrooms[mb_strtolower($raw['classroom'])] ?? null;
            if (! $this->year) {
                $error('no_year');
            } elseif (! $classroomId) {
                $error('classroom', ['value' => $raw['classroom'], 'year' => $this->year->name]);
            }
        }

        $phone = PhoneNumber::normalize($raw['guardian_phone']);
        if ($raw['guardian_phone'] !== null && ! $phone) {
            $error('phone', ['value' => $raw['guardian_phone']]);
        }
        if ($phone && $raw['guardian_name'] === null && ! isset($this->namedPhones[$phone])) {
            $error('guardian_name');
        }
        if ($phone && $raw['guardian_name'] !== null) {
            $this->namedPhones[$phone] = true;
        }

        $relationship = $raw['relationship'] === null ? Relationship::Guardian : (self::RELATIONSHIPS[mb_strtolower($raw['relationship'])] ?? null);
        if (! $relationship) {
            $error('relationship', ['value' => $raw['relationship']]);
        }

        return [
            'line' => $line,
            'data' => [
                'student_code' => $code,
                'last_name_km' => $raw['last_name_km'],
                'first_name_km' => $raw['first_name_km'],
                'last_name_en' => $raw['last_name_en'],
                'first_name_en' => $raw['first_name_en'],
                'gender' => $gender?->value,
                'dob' => $dob,
                'birth_province' => $province,
                'classroom' => $raw['classroom'],
                'classroom_id' => $classroomId,
                'guardian_name' => $raw['guardian_name'],
                'guardian_phone' => $phone,
                'relationship' => $relationship?->value,
            ],
            'errors' => $errors,
        ];
    }

    /** Trimmed text with Khmer digits as ASCII (០១២ → 012); null when empty. Numbers stay numbers (Excel dates). */
    private function clean(mixed $cell): mixed
    {
        if ($cell === null || is_int($cell) || is_float($cell)) {
            return $cell;
        }

        $text = trim(strtr((string) $cell, array_combine(mb_str_split('០១២៣៤៥៦៧៨៩'), range(0, 9))));

        return $text === '' ? null : $text;
    }

    /** A Y-m-d date from an Excel date number or "dd/mm/yyyy", "dd-mm-yyyy", "dd.mm.yyyy", "yyyy-mm-dd". */
    private function date(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $date = is_int($value) || is_float($value)
                ? CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($value))
                : $this->parseDate((string) $value);
        } catch (Throwable) {
            return null;
        }

        return $date && $date->year >= 1950 && $date->isPast() ? $date->toDateString() : null;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $value, $m)) {
            [, $day, $month, $year] = $m;
        } elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $value, $m)) {
            [, $year, $month, $day] = $m;
        } else {
            return null;
        }

        // checkdate refuses 31/02 rather than rolling it into March.
        return checkdate((int) $month, (int) $day, (int) $year)
            ? CarbonImmutable::create((int) $year, (int) $month, (int) $day)
            : null;
    }

    private function personKey(string $last, string $first, string $dob): string
    {
        return mb_strtolower("{$last}|{$first}|{$dob}");
    }
}
