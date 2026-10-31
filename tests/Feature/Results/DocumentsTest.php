<?php

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Actions\EnterScores;
use App\Domain\Exams\Actions\TransitionExamStatus;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\People\Actions\EnrollStudents;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Enums\DocumentStatus;
use App\Domain\Results\ExamDocuments;
use App\Domain\Results\Jobs\GenerateExamDocument;
use App\Domain\Results\Models\ExamDocument;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Files\StoredFile;
use App\Support\Pdf\DocumentFormat;
use App\Support\Pdf\PdfRenderer;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media_private');
    Storage::fake('media_public');

    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    $this->director = member($this->school, Role::Director);
    $this->class = classesFor($this->school, ['7A'])['7A'];
    $this->khm = Subject::where('code', 'KHM')->firstOrFail();

    CurriculumSubject::create([
        'academic_year_id' => $this->class->academic_year_id, 'grade_level_id' => $this->class->grade_level_id,
        'subject_id' => $this->khm->id, 'max_score' => 100, 'coefficient' => 1, 'pass_score' => 50, 'sort' => 0,
    ]);
    GradeDivisor::create(['academic_year_id' => $this->class->academic_year_id, 'grade_level_id' => $this->class->grade_level_id, 'divisor' => 2]);

    // Seven students scoring 90, 80 ... 30: ranks 1 to 7.
    $ids = [];
    foreach (['កែវ', 'ចាន់', 'ពៅ', 'សុខ', 'ម៉ៅ', 'លី', 'ហេង'] as $name) {
        $ids[] = makeStudent($name, 'មុនី')->id;
    }
    app(EnrollStudents::class)->handle($this->class, $ids);
    $this->enrollments = Enrollment::where('classroom_id', $this->class->id)->orderBy('roll_no')->pluck('id')->all();

    $this->exam = Exam::create([
        'academic_year_id' => $this->class->academic_year_id, 'name' => 'ខែវិច្ឆិកា', 'type' => 'monthly', 'month' => 11,
        'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'status' => ExamStatus::Draft,
    ]);
    $this->exam->syncClassrooms([$this->class->id]);
    app(TransitionExamStatus::class)->handle($this->exam, ExamStatus::Open);
    app(EnterScores::class)->handle($this->exam, $this->class, array_map(fn ($id, $i) => [
        'enrollment_id' => $id, 'subject_id' => $this->khm->id, 'value' => (string) (90 - $i * 10),
    ], $this->enrollments, array_keys($this->enrollments)), $this->office);
});

function lockDocumentsExam(): Exam
{
    ExamSubjectStatus::where('exam_id', test()->exam->id)->update(['status' => SubjectExamStatus::Approved]);
    test()->actingAs(test()->office)->post('/exams/'.test()->exam->id.'/transition', ['status' => 'approved'])->assertSessionHasNoErrors();
    test()->actingAs(test()->office)->post('/exams/'.test()->exam->id.'/transition', ['status' => 'locked'])->assertSessionHasNoErrors();

    return test()->exam->refresh();
}

function fakeGotenberg(): void
{
    Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-fake', 200, ['Content-Type' => 'application/pdf'])]);
}

it('sends Gotenberg the HTML with its fonts and paper', function () {
    fakeGotenberg();

    $pdf = app(PdfRenderer::class)->render('pdf.partials.signatures', ['roles' => [], 'format' => new DocumentFormat, 'printedAt' => now()], 'A5', landscape: true);

    expect($pdf)->toBe('%PDF-fake');
    Http::assertSent(function (Request $request) {
        $body = $request->body();

        return str_ends_with($request->url(), '/forms/chromium/convert/html')
            && str_contains($request->header('Content-Type')[0], 'multipart/form-data')
            && str_contains($body, 'filename="index.html"')
            && str_contains($body, 'filename="moul-khmer-400-normal.woff2"')
            && str_contains($body, 'filename="battambang-khmer-400-normal.woff2"')
            && str_contains($body, 'name="paperWidth"') && str_contains($body, '148mm')
            && str_contains($body, 'name="landscape"')
            && str_contains($body, 'window.fontsReady === true');
    });
});

it('generates a class document in the queue and stores it for this version', function () {
    $exam = lockDocumentsExam();
    fakeGotenberg();

    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", [
        'classroom_id' => $this->class->id, 'kind' => 'report_cards', 'paper' => 'A5',
    ])->assertSessionHasNoErrors();

    $document = ExamDocument::firstOrFail();
    expect($document->status)->toBe(DocumentStatus::Ready)
        ->and($document->version)->toBe($exam->results_version)
        ->and($document->paper)->toBe('A5');
    Storage::disk('media_private')->assertExists($document->file->path);
    expect($document->file->path)->toStartWith("schools/{$this->school->id}/exam-documents/{$document->id}/");

    $this->actingAs($this->office)->get("/exams/{$exam->id}/documents/{$document->id}")->assertRedirect();
});

it('replaces the file when a document is generated again, and keeps one row', function () {
    $exam = lockDocumentsExam();
    fakeGotenberg();
    $body = ['classroom_id' => $this->class->id, 'kind' => 'score_sheet'];

    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", $body);
    $first = ExamDocument::firstOrFail()->file->path;
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", $body);

    expect(ExamDocument::count())->toBe(1)
        ->and(StoredFile::where('collection', ExamDocument::COLLECTION)->count())->toBe(1);
    Storage::disk('media_private')->assertMissing($first);
});

it('does not queue a second generation while one is running', function () {
    $exam = lockDocumentsExam();
    Bus::fake();
    $body = ['classroom_id' => $this->class->id, 'kind' => 'score_sheet'];

    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", $body);
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", $body);

    Bus::assertDispatchedTimes(GenerateExamDocument::class, 1);
});

it('marks a document failed when Gotenberg keeps refusing, and allows another try', function () {
    $exam = lockDocumentsExam();
    Bus::fake();
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet']);
    $document = ExamDocument::firstOrFail();

    (new GenerateExamDocument($this->school->id, $document->id))->failed(new RuntimeException('down'));
    expect($document->refresh()->status)->toBe(DocumentStatus::Failed);

    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet']);
    expect($document->refresh()->status)->toBe(DocumentStatus::Pending);
    Bus::assertDispatchedTimes(GenerateExamDocument::class, 2);
});

it('refuses documents before the results are calculated', function () {
    Bus::fake();

    $this->actingAs($this->office)->post("/exams/{$this->exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet'])
        ->assertSessionHasErrors('documents');

    expect(ExamDocument::count())->toBe(0);
    Bus::assertNotDispatched(GenerateExamDocument::class);
});

it('refuses documents while an approved correction waits for its recalculation', function () {
    $exam = lockDocumentsExam();
    $exam->update(['results_computed_at' => now()->subHour()]);
    ScoreChangeRequest::create([
        'exam_id' => $exam->id, 'enrollment_id' => $this->enrollments[0], 'subject_id' => $this->khm->id,
        'old_value' => '90', 'new_value' => '91', 'reason' => 'x', 'status' => 'approved',
        'requested_by' => $this->office->id, 'decided_by' => $this->director->id, 'decided_at' => now(),
    ]);

    expect($exam->refresh()->hasFinalResults())->toBeFalse();
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet'])
        ->assertSessionHasErrors('documents');
    $this->actingAs($this->office)->get("/exams/{$exam->id}/report-cards/{$this->enrollments[0]}")->assertStatus(409);
});

it('drops a queued document whose version a correction has replaced', function () {
    $exam = lockDocumentsExam();
    Bus::fake();
    Http::fake();
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet']);
    $document = ExamDocument::firstOrFail();
    $exam->update(['results_version' => $exam->results_version + 1]);

    app()->call([new GenerateExamDocument($this->school->id, $document->id), 'handle']);

    expect(ExamDocument::find($document->id))->toBeNull();
    Http::assertNothingSent();
});

it('renders one student card on the spot', function () {
    $exam = lockDocumentsExam();
    fakeGotenberg();

    $this->actingAs($this->office)->get("/exams/{$exam->id}/report-cards/{$this->enrollments[0]}")
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');

    Http::assertSent(fn (Request $r) => str_contains($r->body(), 'កែវ មុនី') && ! str_contains($r->body(), 'ចាន់ មុនី'));
});

it('answers 503 when Gotenberg is down', function () {
    $exam = lockDocumentsExam();
    Http::fake(['*' => Http::response('down', 500)]);

    $this->actingAs($this->office)->get("/exams/{$exam->id}/report-cards/{$this->enrollments[0]}")->assertStatus(503);
});

it('prints the stored numbers, ranks and the honor roll', function () {
    $exam = lockDocumentsExam();
    $this->school->update(['settings' => [...$this->school->settings->toArray(), 'khmerNumerals' => true], 'report_header' => 'មន្ទីរ <b>សិក្សា</b>']);
    $school = School::findOrFail($this->school->id);
    $data = app(ExamDocuments::class)->data($exam, $this->class, $school);

    $sheet = view('pdf.score-sheet', $data)->render();
    expect($sheet)->toContain('ព្រះរាជាណាចក្រកម្ពុជា')
        ->toContain('ជាតិ សាសនា ព្រះមហាក្សត្រ')
        ->toContain('កែវ មុនី')
        ->toContain('៤៥.០០')     // 90 / 2
        ->toContain('មន្ទីរ &lt;b&gt;សិក្សា&lt;/b&gt;')
        ->toContain('ថ្ងៃទី');

    $honor = view('pdf.honor-roll', $data)->render();
    $names = Enrollment::whereIn('id', $this->enrollments)->with('student')->get()->sortBy(fn ($e) => array_search($e->id, $this->enrollments))
        ->map(fn ($e) => $e->student->nameKm())->values();
    expect($honor)->toContain($names[4])->not->toContain($names[5])->not->toContain($names[6]);

    $cards = view('pdf.report-card', $data)->render();
    expect(substr_count($cards, 'class="page"'))->toBe(7)->and($cards)->toContain('១ / ៧');
});

it('uses a stored result, not a live one, and leaves out students enrolled after the lock', function () {
    $exam = lockDocumentsExam();
    app(EnrollStudents::class)->handle($this->class, [makeStudent('ថ្មី', 'មក')->id]);

    $data = app(ExamDocuments::class)->data($exam, $this->class, School::findOrFail($this->school->id));

    expect($data['classSize'])->toBe(7)
        ->and($data['rows']->pluck('name_km'))->not->toContain('ថ្មី មក');
});

it('lets only those who may see the score sheet print it', function () {
    $exam = lockDocumentsExam();
    Bus::fake();
    $outsider = member($this->school, Role::SubjectTeacher);

    $this->actingAs($outsider)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet'])->assertForbidden();
    $this->actingAs($outsider)->get("/exams/{$exam->id}/report-cards/{$this->enrollments[0]}")->assertForbidden();
    expect(ExamDocument::count())->toBe(0);
});

it('hides a document from another school', function () {
    $exam = lockDocumentsExam();
    fakeGotenberg();
    $this->actingAs($this->office)->post("/exams/{$exam->id}/documents", ['classroom_id' => $this->class->id, 'kind' => 'score_sheet']);
    $document = ExamDocument::firstOrFail();

    $other = makeSchool();
    $stranger = member($other, Role::AcademicOffice);
    app(CurrentSchool::class)->set(null);

    $this->actingAs($stranger)->get("/exams/{$exam->id}/documents/{$document->id}")->assertNotFound();
    $this->actingAs($stranger)->get("/exams/{$exam->id}/report-cards/{$this->enrollments[0]}")->assertNotFound();
});

it('offers the documents on the results page once locked', function () {
    $exam = lockDocumentsExam();
    $user = User::find($this->office->id);

    $this->actingAs($user)->get("/exams/{$exam->id}/results?classroom={$this->class->id}")
        ->assertInertia(fn ($page) => $page->where('exam.printable', true)->has('documents', 0));
});
