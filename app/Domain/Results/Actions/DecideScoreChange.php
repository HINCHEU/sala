<?php

namespace App\Domain\Results\Actions;

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Exams\Actions\EnterScores;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Enums\ChangeRequestStatus;
use App\Domain\Results\Jobs\CalculateExamResults;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideScoreChange
{
    /**
     * Approving writes the new value (logged with the old one) and recalculates the exam's results as a
     * new version; the version the parents saw stays on record. Rejecting only records the decision.
     */
    public function handle(ScoreChangeRequest $request, bool $approve, ?string $note, User $user): void
    {
        if ($request->status !== ChangeRequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('results.change.already_decided')]);
        }

        DB::transaction(function () use ($request, $approve, $note, $user) {
            if ($approve) {
                $classroom = Enrollment::findOrFail($request->enrollment_id)->classroom()->firstOrFail();
                $max = (float) CurriculumSubject::forClassroom($classroom)->get($request->subject_id)?->max_score;
                $parsed = EnterScores::parse($request->new_value, $max);
                if (! is_array($parsed)) {
                    throw ValidationException::withMessages(['status' => __('results.change.no_longer_valid')]);
                }

                $score = Score::firstOrNew(
                    ['exam_id' => $request->exam_id, 'enrollment_id' => $request->enrollment_id, 'subject_id' => $request->subject_id],
                    ['entered_by' => $user->id],
                );
                $old = $score->exists ? $score->display() : null;
                $score->fill([...$parsed, 'updated_by' => $user->id])->save();

                activity()->performedOn($request)->causedBy($user)
                    ->withProperties(['old' => $old, 'new' => $score->display()])
                    ->log('score.changed_after_lock');

                CalculateExamResults::dispatch($request->school_id, $request->exam_id)->afterCommit();
            }

            $request->update([
                'status' => $approve ? ChangeRequestStatus::Approved : ChangeRequestStatus::Rejected,
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);
        });
    }
}
