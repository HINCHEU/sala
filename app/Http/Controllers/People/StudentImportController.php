<?php

namespace App\Http\Controllers\People;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\People\Actions\PreviewStudentImport;
use App\Domain\People\Enums\ImportStatus;
use App\Domain\People\Import\StudentImportTemplate;
use App\Domain\People\Import\StudentSheet;
use App\Domain\People\Jobs\ImportStudentsJob;
use App\Domain\People\Models\StudentImport;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Upload → check every row → the registrar sees the errors → import the good rows in a queued job. */
class StudentImportController extends Controller
{
    public const PREVIEW_OK_ROWS = 20;

    public function create(): Response
    {
        return Inertia::render('People/Import/Create', [
            'year' => AcademicYear::current()?->only('id', 'name'),
            'columns' => StudentSheet::COLUMNS,
            'imports' => StudentImport::latest('id')->limit(10)
                ->get(['id', 'original_name', 'status', 'row_count', 'error_count', 'imported_count', 'created_at'])
                ->map(fn (StudentImport $i) => [
                    ...$i->only('id', 'original_name', 'row_count', 'error_count', 'imported_count'),
                    'status' => $i->status->value,
                    'created_at' => $i->created_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new StudentImportTemplate, 'sala-students.xlsx');
    }

    public function store(Request $request, PreviewStudentImport $preview): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);

        $import = $preview->handle($request->file('file'), $request->user());

        return redirect()->route('people.import.show', $import);
    }

    public function show(StudentImport $import): Response
    {
        $rows = collect($import->rows);

        return Inertia::render('People/Import/Show', [
            'import' => [
                ...$import->only('id', 'original_name', 'row_count', 'error_count', 'imported_count', 'failure'),
                'status' => $import->status->value,
                'year' => $import->academic_year_id ? AcademicYear::find($import->academic_year_id)?->name : null,
                // Every row with a problem, and a sample of good ones to check the columns lined up.
                'errors' => $rows->filter(fn (array $r) => $r['errors'] !== [])->values(),
                'sample' => $rows->filter(fn (array $r) => $r['errors'] === [])->take(self::PREVIEW_OK_ROWS)->values(),
            ],
        ]);
    }

    public function confirm(StudentImport $import): RedirectResponse
    {
        if ($import->status !== ImportStatus::Previewed) {
            return back()->with('error', __('people.import.already_started'));
        }
        if ($import->error_count >= $import->row_count) {
            return back()->with('error', __('people.import.nothing_to_import'));
        }

        // Claim it first, so a double click cannot queue the same rows twice.
        $claimed = StudentImport::whereKey($import->id)->where('status', ImportStatus::Previewed)
            ->update(['status' => ImportStatus::Queued]);
        if ($claimed) {
            ImportStudentsJob::dispatch($import->school_id, $import->id);
        }

        return redirect()->route('people.import.show', $import);
    }
}
