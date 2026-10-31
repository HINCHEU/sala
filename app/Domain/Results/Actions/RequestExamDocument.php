<?php

namespace App\Domain\Results\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Exams\Models\Exam;
use App\Domain\Results\Enums\DocumentKind;
use App\Domain\Results\Enums\DocumentStatus;
use App\Domain\Results\Jobs\GenerateExamDocument;
use App\Domain\Results\Models\ExamDocument;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestExamDocument
{
    /** A job the worker lost never reports back: after this long a new request may replace it. */
    private const PENDING_MINUTES = 10;

    /**
     * Queues the document for the exam's current version. Asking again regenerates it (a new logo, say),
     * unless a generation is already running.
     */
    public function handle(Exam $exam, Classroom $classroom, DocumentKind $kind, string $paper, User $user): ExamDocument
    {
        if (! $exam->hasFinalResults()) {
            throw ValidationException::withMessages(['documents' => __('documents.not_ready')]);
        }

        $document = ExamDocument::createOrFirst(
            ['exam_id' => $exam->id, 'classroom_id' => $classroom->id, 'version' => $exam->results_version, 'kind' => $kind],
            ['paper' => $paper, 'status' => DocumentStatus::Pending, 'requested_by' => $user->id],
        );

        if (! $document->wasRecentlyCreated) {
            if ($document->status === DocumentStatus::Pending && $document->updated_at->gt(now()->subMinutes(self::PENDING_MINUTES))) {
                return $document;
            }
            $document->update(['paper' => $paper, 'status' => DocumentStatus::Pending, 'requested_by' => $user->id]);
            $document->touch();
        }

        GenerateExamDocument::dispatch($exam->school_id, $document->id);

        return $document;
    }
}
