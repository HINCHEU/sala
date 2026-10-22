<?php

namespace App\Http\Controllers\People;

use App\Domain\People\Enums\DocumentType;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use App\Support\Files\FileStore;
use App\Support\Files\StoredFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Birth certificate, family book and other scans, on the private disk. */
class StudentDocumentController extends Controller
{
    public function store(Request $request, Student $student, FileStore $files): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $files->put($request->file('file'), $student, $data['type'], $student->school_id);
        activity()->performedOn($student)->causedBy(auth()->user())->withProperties(['type' => $data['type']])->log('document.added');

        return back()->with('success', __('people.saved'));
    }

    public function destroy(Student $student, StoredFile $document, FileStore $files): RedirectResponse
    {
        // StoredFile is not tenant-scoped: it must be this student's document, which the scoped binding vouches for.
        abort_unless(
            $document->owner_type === $student->getMorphClass()
                && (int) $document->owner_id === $student->id
                && DocumentType::tryFrom($document->collection),
            404,
        );

        $files->delete($document);
        activity()->performedOn($student)->causedBy(auth()->user())->withProperties(['type' => $document->collection])->log('document.deleted');

        return back()->with('success', __('people.deleted'));
    }
}
