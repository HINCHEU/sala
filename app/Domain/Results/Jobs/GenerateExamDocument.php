<?php

namespace App\Domain\Results\Jobs;

use App\Domain\Exams\Models\Exam;
use App\Domain\Results\Enums\DocumentStatus;
use App\Domain\Results\ExamDocuments;
use App\Domain\Results\Models\ExamDocument;
use App\Domain\School\Models\School;
use App\Support\Files\FileStore;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Renders one document and stores it, replacing the file of an earlier generation. Carries school_id
 * and restores the current school first. Safe to retry: nothing is stored until the PDF exists.
 */
class GenerateExamDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    // Below the queue's retry_after (90 s), so a slow render is never picked up twice.
    public int $timeout = 80;

    public function __construct(public int $schoolId, public int $documentId) {}

    public function handle(CurrentSchool $current, ExamDocuments $documents, FileStore $files): void
    {
        $current->runAs(School::findOrFail($this->schoolId), function () use ($documents, $files) {
            $document = ExamDocument::with('classroom')->findOrFail($this->documentId);
            $exam = Exam::findOrFail($document->exam_id);

            // A correction since the request: this version is history, and its scores have changed.
            if ($exam->results_version !== $document->version) {
                $document->delete();

                return;
            }
            if (! $exam->hasFinalResults()) {
                throw new RuntimeException('The results are being recalculated.');
            }

            $pdf = $documents->render($exam, $document->classroom, $document->kind, $document->paper);

            $previous = $document->file;
            $files->putContents($pdf, 'pdf', 'application/pdf', $document, ExamDocument::COLLECTION, $this->schoolId, originalName: "{$document->kind->value}.pdf");
            if ($previous) {
                $files->delete($previous);
            }
            $document->update(['status' => DocumentStatus::Ready]);
        });
    }

    public function failed(?Throwable $e): void
    {
        ExamDocument::withoutGlobalScope('school')->whereKey($this->documentId)->update(['status' => DocumentStatus::Failed]);
    }
}
