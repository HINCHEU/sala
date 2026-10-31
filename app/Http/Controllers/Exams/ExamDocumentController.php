<?php

namespace App\Http\Controllers\Exams;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Actions\RequestExamDocument;
use App\Domain\Results\Enums\DocumentKind;
use App\Domain\Results\Enums\DocumentStatus;
use App\Domain\Results\ExamDocuments;
use App\Domain\Results\Models\ExamDocument;
use App\Http\Controllers\Controller;
use App\Support\Files\FileStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** Printed documents: whoever may see a class's score sheet may print it. */
class ExamDocumentController extends Controller
{
    public function __construct(private ExamPolicy $policy) {}

    public function store(Request $request, Exam $exam, RequestExamDocument $action): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'kind' => ['required', Rule::enum(DocumentKind::class)],
            'paper' => ['sometimes', Rule::in(['A4', 'A5'])],
        ]);
        $classroom = $this->classroom($request, $exam, (int) $data['classroom_id']);

        $action->handle($exam, $classroom, DocumentKind::from($data['kind']), $data['paper'] ?? 'A4', $request->user());

        return back()->with('success', __('documents.queued'));
    }

    public function download(Request $request, Exam $exam, ExamDocument $document, FileStore $files): RedirectResponse
    {
        abort_unless($document->exam_id === $exam->id && $document->status === DocumentStatus::Ready && $document->file, 404);
        $this->classroom($request, $exam, $document->classroom_id);

        return redirect()->away($files->temporaryUrl($document->file));
    }

    /** One student's card, rendered on the spot: quick, and nothing to keep. */
    public function reportCard(Request $request, Exam $exam, Enrollment $enrollment, ExamDocuments $documents): Response
    {
        $classroom = $this->classroom($request, $exam, $enrollment->classroom_id);
        abort_unless($exam->hasFinalResults(), 409, __('documents.not_ready'));

        try {
            $pdf = $documents->render($exam, $classroom, DocumentKind::ReportCards, $request->query('paper') === 'A5' ? 'A5' : 'A4', $enrollment->id);
        } catch (ConnectionException|RequestException $e) {
            report($e);
            abort(503, __('documents.failed'));
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="report-card-'.$enrollment->id.'.pdf"',
        ]);
    }

    private function classroom(Request $request, Exam $exam, int $classroomId): Classroom
    {
        $classroom = $exam->classrooms()->where('classrooms.id', $classroomId)->first();
        abort_unless($classroom !== null, 404);
        abort_unless($this->policy->viewScores($request->user(), $exam, $classroom), 403);

        return $classroom;
    }
}
