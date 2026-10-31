<?php

namespace App\Domain\Results;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Exams\Models\Exam;
use App\Domain\Results\Enums\DocumentKind;
use App\Domain\School\Models\School;
use App\Support\Files\FileStore;
use App\Support\Pdf\DocumentFormat;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Support\Traits\Localizable;

/**
 * The printed documents of a locked exam, in the school's language. Numbers come from the same sheet as
 * the results page, restricted to students with a stored result, so paper and screen always agree.
 */
class ExamDocuments
{
    use Localizable;

    /** Students with these class ranks make the honor roll; ties all stay in. */
    public const HONOR_ROLL_RANKS = 5;

    public function __construct(private ClassResults $classResults, private PdfRenderer $pdf, private FileStore $files) {}

    public function render(Exam $exam, Classroom $classroom, DocumentKind $kind, string $paper = 'A4', ?int $enrollmentId = null): string
    {
        $school = School::findOrFail($exam->school_id);

        return $this->withLocale($school->settings->locale, function () use ($exam, $classroom, $kind, $paper, $enrollmentId, $school) {
            $data = $this->data($exam, $classroom, $school, $paper, $enrollmentId);

            return match ($kind) {
                DocumentKind::ScoreSheet => $this->pdf->render('pdf.score-sheet', $data, 'A4', landscape: true),
                DocumentKind::ReportCards => $this->pdf->render('pdf.report-card', $data, $paper),
                DocumentKind::HonorRoll => $this->pdf->render('pdf.honor-roll', $data),
            };
        });
    }

    /** @return array<string, mixed> */
    public function data(Exam $exam, Classroom $classroom, School $school, string $paper = 'A4', ?int $enrollmentId = null): array
    {
        $classroom->loadMissing('gradeLevel', 'homeroomTeacher');
        $sheet = $this->classResults->sheet($exam, $classroom);
        $rows = collect($sheet['rows'])->where('stored', true)->values();
        $classSize = $rows->count();
        $students = $classroom->enrollments()->with('student')->whereIn('id', $rows->pluck('enrollment_id'))->get()->keyBy('id');

        return [
            'school' => $school,
            'logo' => $this->logo($school),
            'format' => new DocumentFormat($school->settings->khmerNumerals),
            'exam' => $exam,
            'classroom' => $classroom,
            'year' => AcademicYear::find($classroom->academic_year_id),
            'subjects' => $sheet['subjects'],
            'rows' => $rows->when($enrollmentId !== null, fn ($r) => $r->where('enrollment_id', $enrollmentId)->values()),
            'classSize' => $classSize,
            'girls' => $rows->where('gender', 'female')->count(),
            'passed' => $rows->where('passed', true)->count(),
            'students' => $students,
            'honorRoll' => $rows->filter(fn (array $r) => $r['rank_class'] !== null && $r['rank_class'] <= self::HONOR_ROLL_RANKS)
                ->sortBy(fn (array $r) => [$r['rank_class'], $r['roll_no']])->values(),
            'paper' => $paper,
            'printedAt' => now('Asia/Phnom_Penh'),
        ];
    }

    private function logo(School $school): ?string
    {
        $logo = $school->logo;

        return $logo ? 'data:'.$logo->mime.';base64,'.base64_encode($this->files->contents($logo)) : null;
    }
}
