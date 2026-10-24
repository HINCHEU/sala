<?php

namespace App\Http\Controllers\Exams;

use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Domain\Results\Actions\DecideScoreChange;
use App\Domain\Results\Actions\RequestScoreChange;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Domain\School\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Corrections after an exam is locked: the subject's teacher asks, the director decides. */
class ScoreChangeController extends Controller
{
    public function store(Request $request, Exam $exam, ExamPolicy $policy, RequestScoreChange $requestChange): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'enrollment_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'new_value' => ['required', 'string', 'max:10'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $classroom = $exam->classrooms()->where('classrooms.id', $data['classroom_id'])->first();
        $subject = Subject::find($data['subject_id']);
        abort_unless($classroom !== null && $subject !== null, 404);
        abort_unless($policy->requestScoreChange($request->user(), $exam, $classroom, $subject), 403);

        $requestChange->handle($exam, $classroom, (int) $data['enrollment_id'], $subject->id, $data['new_value'], $data['reason'], $request->user());

        return back()->with('success', __('results.change.requested'));
    }

    public function decide(Request $request, Exam $exam, ScoreChangeRequest $change, DecideScoreChange $decide): RedirectResponse
    {
        abort_unless((int) $change->exam_id === $exam->id, 404);
        abort_unless($request->user()->can(Permission::ApproveScoreChanges->value), 403);
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $decide->handle($change, (bool) $data['approve'], $data['note'] ?? null, $request->user());

        return back()->with('success', __($data['approve'] ? 'results.change.approved' : 'results.change.rejected'));
    }
}
