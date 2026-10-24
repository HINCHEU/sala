<?php

namespace App\Domain\Results\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Exams\Actions\EnterScores;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Enums\ChangeRequestStatus;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestScoreChange
{
    /** Asks for one cell of a locked exam to change. The new value is checked like a typed one. */
    public function handle(Exam $exam, Classroom $classroom, int $enrollmentId, int $subjectId, string $value, string $reason, User $user): ScoreChangeRequest
    {
        $fail = fn (string $key, array $replace = []) => throw ValidationException::withMessages(['new_value' => __($key, $replace)]);

        if (! Enrollment::where('classroom_id', $classroom->id)->whereKey($enrollmentId)->exists()) {
            $fail('exams.errors.not_in_class');
        }
        $max = CurriculumSubject::forClassroom($classroom)->get($subjectId)?->max_score;
        if ($max === null) {
            $fail('exams.errors.no_max_score');
        }
        $parsed = EnterScores::parse($value, (float) $max);
        if ($parsed === null) {
            $fail('results.change.value_required');
        }
        if (is_string($parsed)) {
            $fail("exams.errors.{$parsed}", ['max' => rtrim(rtrim((string) $max, '0'), '.')]);
        }

        $current = Score::where('exam_id', $exam->id)->where('enrollment_id', $enrollmentId)->where('subject_id', $subjectId)->first();
        $newDisplay = (new Score($parsed))->display();
        if ($current?->display() === $newDisplay) {
            $fail('results.change.same_value');
        }
        if (ScoreChangeRequest::where('exam_id', $exam->id)->where('enrollment_id', $enrollmentId)->where('subject_id', $subjectId)
            ->where('status', ChangeRequestStatus::Pending)->exists()) {
            $fail('results.change.already_pending');
        }

        $request = ScoreChangeRequest::create([
            'exam_id' => $exam->id,
            'enrollment_id' => $enrollmentId,
            'subject_id' => $subjectId,
            'old_value' => $current?->display(),
            'new_value' => $newDisplay,
            'reason' => $reason,
            'status' => ChangeRequestStatus::Pending,
            'requested_by' => $user->id,
        ]);

        activity()->performedOn($request)->causedBy($user)->log('score_change.requested');

        return $request;
    }
}
