<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Exams\Enums\ScoreMark;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Saves score cells for one class: typed one at a time, pasted from Excel, or imported from a sheet.
 * Each cell is checked on its own and reported back; one bad cell never blocks the others.
 * Last write wins, and every change is logged with who made it and the value it replaced.
 */
class EnterScores
{
    /**
     * @param  list<array{enrollment_id: int, subject_id: int, value: ?string}>  $cells
     * @return list<array{enrollment_id: int, subject_id: int, ok: bool, value: ?string, error: ?string}>
     */
    public function handle(Exam $exam, Classroom $classroom, array $cells, User $user): array
    {
        // 423 Locked: the exam is not taking scores (still a draft, approved, locked or published).
        if (! $exam->isOpen()) {
            throw new HttpException(423, __('exams.errors.not_open_for_scores'));
        }

        $subjectIds = array_values(array_unique(array_column($cells, 'subject_id')));
        $sheets = ExamSubjectStatus::where('exam_id', $exam->id)->where('classroom_id', $classroom->id)
            ->whereIn('subject_id', $subjectIds)->get()->keyBy('subject_id');
        $curriculum = CurriculumSubject::forClassroom($classroom);
        $enrollments = Enrollment::where('classroom_id', $classroom->id)->pluck('id')->flip();
        $existing = Score::where('exam_id', $exam->id)->whereIn('subject_id', $subjectIds)
            ->whereIn('enrollment_id', array_column($cells, 'enrollment_id'))->get()
            ->keyBy(fn (Score $s) => "{$s->enrollment_id}-{$s->subject_id}");

        return DB::transaction(fn () => array_map(function (array $cell) use ($exam, $sheets, $curriculum, $enrollments, $existing, $user) {
            $key = "{$cell['enrollment_id']}-{$cell['subject_id']}";
            $result = ['enrollment_id' => $cell['enrollment_id'], 'subject_id' => $cell['subject_id']];
            $fail = fn (string $error, array $replace = []) => [...$result, 'ok' => false, 'value' => $existing->get($key)?->display(), 'error' => __("exams.errors.{$error}", $replace)];

            $sheet = $sheets->get($cell['subject_id']);
            if (! $sheet) {
                return $fail('subject_not_in_exam');
            }
            if (! in_array($sheet->status, [SubjectExamStatus::Draft, SubjectExamStatus::Returned], true)) {
                return $fail('subject_sealed', ['status' => $sheet->status->label()]);
            }
            if (! $enrollments->has($cell['enrollment_id'])) {
                return $fail('not_in_class');
            }
            $max = $curriculum->get($cell['subject_id'])?->max_score;
            if ($max === null) {
                return $fail('no_max_score');
            }

            $parsed = self::parse($cell['value'], (float) $max);
            if (is_string($parsed)) {
                return $fail($parsed, ['max' => rtrim(rtrim((string) $max, '0'), '.')]);
            }

            $old = $existing->get($key);
            $oldDisplay = $old?->display();

            // $existing is kept current, so a paste that hits one cell twice updates instead of inserting twice.
            if ($parsed === null) {
                $old?->delete();
                $existing->forget($key);
                $newDisplay = null;
            } else {
                $score = $old ?? new Score(['exam_id' => $exam->id, 'enrollment_id' => $cell['enrollment_id'], 'subject_id' => $cell['subject_id'], 'entered_by' => $user->id]);
                $score->fill([...$parsed, 'updated_by' => $user->id])->save();
                $existing->put($key, $score);
                $newDisplay = $score->display();
            }

            if ($oldDisplay !== $newDisplay) {
                activity()->performedOn($sheet)->causedBy($user)
                    ->withProperties(['enrollment_id' => $cell['enrollment_id'], 'subject_id' => $cell['subject_id'], 'old' => $oldDisplay, 'new' => $newDisplay])
                    ->log('score.entered');
            }

            return [...$result, 'ok' => true, 'value' => $newDisplay, 'error' => null];
        }, $cells));
    }

    /**
     * A typed cell as stored values: null to clear it, ['score', 'mark'] to save it, or an error key.
     * Accepts Khmer digits, a comma for the decimal point, A / E (or អ / ល) for absent / exempt.
     *
     * @return array{score: ?string, mark: ScoreMark}|string|null
     */
    public static function parse(?string $value, float $max): array|string|null
    {
        $value = trim(strtr((string) $value, array_combine(mb_str_split('០១២៣៤៥៦៧៨៩'), range(0, 9)) + [',' => '.']));

        if ($value === '') {
            return null; // blank is not 0: the cell is simply empty
        }
        if ($mark = ScoreMark::fromLetter($value)) {
            return ['score' => null, 'mark' => $mark];
        }
        if (! preg_match('/^\d{1,3}(\.\d{1,2})?$/', $value)) {
            return preg_match('/^\d+\.\d{3,}$/', $value) ? 'two_decimals' : 'not_a_score';
        }
        if ((float) $value > $max) {
            return 'above_max';
        }

        return ['score' => $value, 'mark' => ScoreMark::Present];
    }
}
