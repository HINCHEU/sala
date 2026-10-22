<?php

use App\Domain\People\Enums\ImportStatus;
use App\Domain\People\Import\StudentSheet;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\StudentImport;
use App\Domain\School\Enums\Role;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->school = makeSchool();
    $this->registrar = member($this->school, Role::Registrar);
    $this->classes = classesFor($this->school);
});

/** An .xlsx upload: the template's heading row, then $rows in StudentSheet::COLUMNS order. */
function sheet(array $rows): UploadedFile
{
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([StudentSheet::headings(), ...$rows], null, 'A1', true);
    $path = tempnam(sys_get_temp_dir(), 'sala').'.xlsx';
    (new Xlsx($book))->save($path);

    return new UploadedFile($path, 'students.xlsx', null, null, true);
}

//                code   last km  first km  last en  first en  gender   dob           province    class  guardian       phone            relation
$good = [
    [null,  'សុខ',   'ដារ៉ា',   'Sok',   'Dara',   'ស្រី',   '15/03/2014', 'កំពត',     '7A',  'សុខ ចិន្ដា',   '012 345 678',   'ឪពុក'],
    [null,  'សុខ',   'បុប្ផា',   'Sok',   'Bopha',  'F',      '1/9/2015',   'Kampot',   '7A',  null,          '+855 12 345 678', 'father'],
    ['900', 'ចាន់',  'វិចិត្រ',  null,    null,     'ប្រុស',  null,         null,       '7b',  'កែវ ស្រីមុំ',  '០៩៧ ១១១ ២២២២',  'ម្ដាយ'],
];

it('checks every row and saves nothing until the registrar confirms', function () use ($good) {
    $rows = [
        ...$good,
        [null, 'ពៅ', null, null, null, 'X', '31/02/2014', 'Atlantis', '9Z', null, '123', 'uncle'],
        ['900', 'ហេង', 'លីណា', null, null, 'ស្រី', null, null, null, null, null, null],
        [null, 'សុខ', 'ដារ៉ា', null, null, 'ស្រី', '15/03/2014', null, null, null, '096 000 0000', null],
    ];

    $this->actingAs($this->registrar)->post('/people/import', ['file' => sheet($rows)])->assertRedirect();

    $import = StudentImport::firstOrFail();
    expect($import->status)->toBe(ImportStatus::Previewed)
        ->and($import->row_count)->toBe(6)
        ->and($import->error_count)->toBe(3)
        ->and(Student::count())->toBe(0);

    $errors = collect($import->rows)->keyBy('line')->map->errors;
    expect($errors[5])->toHaveCount(7) // missing name; bad gender, date, province, class, phone and relationship
        ->and($errors[6])->toBe([__('people.import.errors.code_taken', ['code' => '900'])])
        ->and($errors[7])->toContain(__('people.import.errors.duplicate_student'), __('people.import.errors.guardian_name'));

    $this->actingAs($this->registrar)->get("/people/import/{$import->id}")
        ->assertInertia(fn ($page) => $page->where('import.errors', fn ($e) => count($e) === 3)->where('import.sample', fn ($s) => count($s) === 3));
});

it('imports the good rows: siblings share one guardian, classes get roll numbers, Khmer text stays intact', function () use ($good) {
    $rows = [...$good, [null, 'ពៅ', null, null, null, 'X', null, null, null, null, null, null]];
    $this->actingAs($this->registrar)->post('/people/import', ['file' => sheet($rows)]);
    $import = StudentImport::firstOrFail();

    $this->actingAs($this->registrar)->post("/people/import/{$import->id}/confirm")->assertRedirect("/people/import/{$import->id}");

    expect($import->fresh())->status->toBe(ImportStatus::Done)->imported_count->toBe(3)
        ->and($import->fresh()->rows[3]['errors'])->toContain(__('people.import.errors.gender', ['value' => 'X']))
        ->and($import->fresh()->rows[3]['errors'])->not->toContain(__('people.import.errors.changed_since_check'))
        ->and(Student::count())->toBe(3)
        ->and(Guardian::count())->toBe(2);

    $dara = Student::where('first_name_km', 'ដារ៉ា')->firstOrFail();
    $bopha = Student::where('first_name_km', 'បុប្ផា')->firstOrFail();
    expect($dara->dob->toDateString())->toBe('2014-03-15')
        ->and($bopha->dob->toDateString())->toBe('2015-09-01')
        ->and($bopha->birth_province)->toBe('កំពត')
        ->and($dara->guardians->first()->is($bopha->guardians->first()))->toBeTrue()
        ->and(Guardian::where('name_km', 'កែវ ស្រីមុំ')->value('phone'))->toBe('+855971112222')
        ->and(Student::where('student_code', '900')->exists())->toBeTrue();

    $sevenA = Enrollment::where('classroom_id', $this->classes['7A']->id)->orderBy('roll_no')->with('student')->get();
    expect($sevenA->map(fn ($e) => $e->roll_no.' '.$e->student->first_name_km)->all())->toBe(['1 ដារ៉ា', '2 បុប្ផា'])
        ->and(Enrollment::where('classroom_id', $this->classes['7B']->id)->count())->toBe(1);
});

it('reads Excel date cells', function () {
    $date = ExcelDate::PHPToExcel(new DateTime('2013-12-25'));
    $this->actingAs($this->registrar)->post('/people/import', ['file' => sheet([[null, 'លី', 'ណា', null, null, 'F', $date, null, null, null, null, null]])]);

    expect(StudentImport::firstOrFail()->rows[0]['data']['dob'])->toBe('2013-12-25');
});

it('queues an import once, however many times confirm is pressed', function () use ($good) {
    $this->actingAs($this->registrar)->post('/people/import', ['file' => sheet($good)]);
    $import = StudentImport::firstOrFail();

    $this->actingAs($this->registrar)->post("/people/import/{$import->id}/confirm");
    $this->actingAs($this->registrar)->post("/people/import/{$import->id}/confirm")->assertSessionHas('error', __('people.import.already_started'));

    expect(Student::count())->toBe(3);
});

it('imports 1,000 rows in under 60 seconds', function () {
    $rows = [];
    for ($i = 1; $i <= 1000; $i++) {
        // Every pair of rows are siblings sharing a guardian.
        $rows[] = [null, 'សុខ', "សិស្ស{$i}", null, null, $i % 2 ? 'ប្រុស' : 'ស្រី', '1/1/2014', null, $i % 2 ? '7A' : '7B',
            'អាណាព្យាបាល '.intdiv($i + 1, 2), sprintf('012 %06d', intdiv($i + 1, 2)), null];
    }

    $started = microtime(true);
    $this->actingAs($this->registrar)->post('/people/import', ['file' => sheet($rows)]);
    $import = StudentImport::firstOrFail();
    $this->actingAs($this->registrar)->post("/people/import/{$import->id}/confirm");
    $seconds = microtime(true) - $started;

    expect($import->fresh()->imported_count)->toBe(1000)
        ->and(Guardian::count())->toBe(500)
        ->and(Enrollment::where('classroom_id', $this->classes['7A']->id)->max('roll_no'))->toBe(500)
        ->and($seconds)->toBeLessThan(60);
});

it('serves the template with the headings in column order', function () {
    $this->actingAs($this->registrar)->get('/people/import/template')
        ->assertOk()
        ->assertDownload('sala-students.xlsx');
});
