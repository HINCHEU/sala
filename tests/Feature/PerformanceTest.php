<?php

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Actions\TransitionExamStatus;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Student;
use App\Domain\Results\Actions\ComputeExamResults;
use App\Domain\School\Enums\Role;
use Illuminate\Support\Facades\DB;

// Targets from the plan (M8): grid load under 1 s for 50 × 15, results under 10 s per grade.
// PERF_CLASSES sets the classes in the grade (default 6; 36 is a 1,800-student grade).

/** @return array{0: Exam, 1: Classroom} an open exam with $classes classes of 50, 15 subjects, every score filled in */
function largeExam(int $classes): array
{
    $school = test()->school;
    $first = classesFor($school, ['A0'])['A0'];
    $classrooms = collect([$first]);
    foreach (range(1, $classes - 1) as $i) {
        $classrooms->push(Classroom::create(['academic_year_id' => $first->academic_year_id, 'grade_level_id' => $first->grade_level_id, 'name' => "A{$i}", 'shift' => 'morning']));
    }

    $subjects = Subject::query()->limit(15)->get();
    while ($subjects->count() < 15) {
        $n = $subjects->count();
        $subjects->push(Subject::create(['code' => "P{$n}", 'name_km' => "មុខវិជ្ជា{$n}", 'name_en' => "Subject {$n}", 'category' => 'core']));
    }
    foreach ($subjects as $i => $subject) {
        CurriculumSubject::create(['academic_year_id' => $first->academic_year_id, 'grade_level_id' => $first->grade_level_id, 'subject_id' => $subject->id, 'max_score' => 100, 'coefficient' => 1, 'pass_score' => 50, 'sort' => $i]);
    }
    GradeDivisor::create(['academic_year_id' => $first->academic_year_id, 'grade_level_id' => $first->grade_level_id, 'divisor' => 30]);

    $exam = Exam::create(['academic_year_id' => $first->academic_year_id, 'name' => 'Large', 'type' => 'monthly', 'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'status' => ExamStatus::Draft]);
    $exam->syncClassrooms($classrooms->pluck('id')->all());
    app(TransitionExamStatus::class)->handle($exam, ExamStatus::Open);

    $now = now();
    $code = 0;
    foreach ($classrooms as $classroom) {
        foreach (range(1, 50) as $roll) {
            $code++;
            $student = Student::create(['student_code' => "L{$code}", 'last_name_km' => 'សុខ', 'first_name_km' => "ដារ៉ា{$code}", 'gender' => 'female', 'status' => 'active']);
            $enrollment = Enrollment::create(['academic_year_id' => $first->academic_year_id, 'classroom_id' => $classroom->id, 'student_id' => $student->id, 'roll_no' => $roll]);
            DB::table('scores')->insert($subjects->map(fn (Subject $s) => [
                'school_id' => $school->id, 'exam_id' => $exam->id, 'enrollment_id' => $enrollment->id, 'subject_id' => $s->id,
                'score' => ($code * 7 + $s->id * 3) % 100, 'mark' => 'present', 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        }
    }

    return [$exam, $first];
}

beforeEach(function () {
    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    [$this->exam, $this->classroom] = largeExam((int) env('PERF_CLASSES', 6));
});

/** @return array{0: float, 1: int} seconds and queries */
function measure(callable $work): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $start = microtime(true);
    $work();
    $seconds = microtime(true) - $start;
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();
    fwrite(STDERR, sprintf("\n  %.3f s, %d queries", $seconds, $queries));

    return [$seconds, $queries];
}

it('loads the score grid for 50 × 15 within a second', function () {
    [$seconds, $queries] = measure(fn () => $this->actingAs($this->office)
        ->get("/exams/{$this->exam->id}/scores?classroom={$this->classroom->id}")->assertOk());

    expect($seconds)->toBeLessThan(1.0)->and($queries)->toBeLessThan(50);
});

it('loads the class result sheet without a query per student', function () {
    [, $queries] = measure(fn () => $this->actingAs($this->office)
        ->get("/exams/{$this->exam->id}/results?classroom={$this->classroom->id}")->assertOk());

    expect($queries)->toBeLessThan(60);
});

it('calculates a grade within ten seconds', function () {
    $this->exam->update(['status' => ExamStatus::Locked]);

    [$seconds] = measure(fn () => app(ComputeExamResults::class)->handle($this->exam));

    expect($seconds)->toBeLessThan(10.0);
});
