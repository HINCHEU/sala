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
use App\Domain\Results\Models\ExamResult;
use App\Domain\School\Enums\Role;

// Accepted when (M6): a past month recalculated by the system matches the school's paper results 100%.
// The sheet lives in tests/Fixtures/results/grade7-monthly.php; swap in the pilot school's real one.

it('matches the paper score sheet to the last digit', function () {
    $sheet = require base_path('tests/Fixtures/results/grade7-monthly.php');
    $school = makeSchool();
    $office = member($school, Role::AcademicOffice);
    $class = classesFor($school, ['7A'])['7A'];

    $subjects = [];
    foreach ($sheet['subjects'] as $i => $s) {
        $subjects[$s['code']] = Subject::where('code', $s['code'])->firstOrFail();
        CurriculumSubject::create([
            'academic_year_id' => $class->academic_year_id, 'grade_level_id' => $class->grade_level_id,
            'subject_id' => $subjects[$s['code']]->id, 'max_score' => $s['max'], 'coefficient' => 1, 'pass_score' => $s['pass'], 'sort' => $i,
        ]);
    }
    GradeDivisor::create(['academic_year_id' => $class->academic_year_id, 'grade_level_id' => $class->grade_level_id, 'divisor' => $sheet['divisor']]);

    $students = array_map(fn ($row) => makeStudent(...$row['name'])->id, $sheet['students']);
    app(EnrollStudents::class)->handle($class, $students);
    $enrollments = Enrollment::where('classroom_id', $class->id)->pluck('id', 'student_id');

    $exam = Exam::create([
        'academic_year_id' => $class->academic_year_id, 'name' => 'Golden', 'type' => 'monthly',
        'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'status' => ExamStatus::Draft,
    ]);
    $exam->syncClassrooms([$class->id]);
    app(TransitionExamStatus::class)->handle($exam, ExamStatus::Open);

    $cells = [];
    foreach ($sheet['students'] as $i => $row) {
        foreach ($row['scores'] as $code => $value) {
            $cells[] = ['enrollment_id' => $enrollments[$students[$i]], 'subject_id' => $subjects[$code]->id, 'value' => (string) $value];
        }
    }
    $saved = app(EnterScores::class)->handle($exam, $class, $cells, $office);
    expect(collect($saved)->where('ok', false))->toBeEmpty();

    ExamSubjectStatus::where('exam_id', $exam->id)->update(['status' => SubjectExamStatus::Approved]);
    $this->actingAs($office)->post("/exams/{$exam->id}/transition", ['status' => 'approved'])->assertSessionHasNoErrors();
    $this->actingAs($office)->post("/exams/{$exam->id}/transition", ['status' => 'locked'])->assertSessionHasNoErrors();

    $results = ExamResult::where('exam_id', $exam->id)->get()->keyBy('enrollment_id');
    foreach ($sheet['students'] as $i => $row) {
        [$total, $average, $rank, $mention, $passed] = $row['expect'];
        $result = $results[$enrollments[$students[$i]]];
        $who = implode(' ', $row['name']);

        expect((float) $result->total)->toBe((float) $total, "total of {$who}")
            ->and((float) $result->average)->toBe((float) $average, "average of {$who}")
            ->and($result->rank_class)->toBe($rank, "rank of {$who}")
            ->and($result->mention)->toBe($mention, "mention of {$who}")
            ->and($result->passed)->toBe($passed, "pass of {$who}");
    }
});
