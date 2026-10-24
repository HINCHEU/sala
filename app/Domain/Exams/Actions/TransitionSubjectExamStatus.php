<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionSubjectExamStatus
{
    public function handle(
        ExamSubjectStatus $record,
        SubjectExamStatus $targetStatus,
        ?User $user = null,
        ?string $reason = null,
    ): ExamSubjectStatus {
        if (! $record->status->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => __('exams.errors.invalid_subject_status_transition', [
                    'from' => $record->status->label(),
                    'to' => $targetStatus->label(),
                ]),
            ]);
        }

        // Submitting means the sheet is finished: every student in the class has a score or a mark.
        if ($targetStatus === SubjectExamStatus::Submitted) {
            $students = Enrollment::where('classroom_id', $record->classroom_id)->pluck('id');
            $missing = $students->count() - Score::where('exam_id', $record->exam_id)
                ->where('subject_id', $record->subject_id)->whereIn('enrollment_id', $students)->count();
            if ($missing > 0) {
                throw ValidationException::withMessages([
                    'status' => __('exams.errors.incomplete', ['count' => $missing]),
                ]);
            }
        }

        if ($targetStatus === SubjectExamStatus::Returned && empty(trim((string) $reason))) {
            throw ValidationException::withMessages([
                'reason' => __('exams.errors.return_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($record, $targetStatus, $user, $reason) {
            $oldStatus = $record->status;
            $updates = ['status' => $targetStatus];

            if ($targetStatus === SubjectExamStatus::Submitted) {
                $updates['submitted_by'] = $user?->id;
                $updates['submitted_at'] = now();
            } elseif ($targetStatus === SubjectExamStatus::Approved) {
                $updates['approved_by'] = $user?->id;
                $updates['approved_at'] = now();
                $updates['returned_reason'] = null;
            } elseif ($targetStatus === SubjectExamStatus::Returned) {
                $updates['returned_reason'] = trim((string) $reason);
            }

            $record->update($updates);

            activity()
                ->performedOn($record)
                ->causedBy($user ?? auth()->user())
                ->withProperties([
                    'exam_id' => $record->exam_id,
                    'classroom_id' => $record->classroom_id,
                    'subject_id' => $record->subject_id,
                    'from' => $oldStatus->value,
                    'to' => $targetStatus->value,
                    'reason' => $reason,
                ])
                ->log('exam.subject_status_changed');

            return $record;
        });
    }
}
