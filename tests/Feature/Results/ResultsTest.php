<?php

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Term;
use App\Domain\Exams\Actions\EnterScores;
use App\Domain\Exams\Actions\TransitionExamStatus;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Actions\EnrollStudents;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Teacher;
use App\Domain\Results\Jobs\CalculateExamResults;
use App\Domain\Results\Models\ExamResult;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Domain\School\Enums\Role;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    $this->director = member($this->school, Role::Director);
    $this->classes = classesFor($this->school);
    [$this->sevenA, $this->sevenB] = [$this->classes['7A'], $this->classes['7B']];
    $this->khm = Subject::where('code', 'KHM')->firstOrFail();
    $this->math = Subject::where('code', 'MATH')->firstOrFail();

    // KHM /100 and MATH /50 with divisor 3: averages out of 50, pass line (50 + 25) / 3 = 25.00.
    foreach ([[$this->khm, 100, 50], [$this->math, 50, 25]] as $i => [$subject, $max, $pass]) {
        CurriculumSubject::create([
            'academic_year_id' => $this->sevenA->academic_year_id, 'grade_level_id' => $this->sevenA->grade_level_id,
            'subject_id' => $subject->id, 'max_score' => $max, 'coefficient' => 1, 'pass_score' => $pass, 'sort' => $i,
        ]);
    }
    GradeDivisor::create(['academic_year_id' => $this->sevenA->academic_year_id, 'grade_level_id' => $this->sevenA->grade_level_id, 'divisor' => 3]);

    $this->a = enrollAll($this->sevenA, [['កែវ', 'មុនី'], ['ចាន់', 'សុភា'], ['ពៅ', 'វណ្ណា'], ['សុខ', 'ដារ៉ា']]);
    $this->b = enrollAll($this->sevenB, [['ម៉ៅ', 'រតនា'], ['លី', 'ស្រីនិច']]);
});

/** @return list<int> enrollment ids in roll order */
function enrollAll($classroom, array $names): array
{
    $ids = array_map(fn ($n) => makeStudent($n[0], $n[1])->id, $names);
    app(EnrollStudents::class)->handle($classroom, $ids);

    return Enrollment::where('classroom_id', $classroom->id)->orderBy('roll_no')->pluck('id')->all();
}

/** An open exam for both classes; $attributes can set the term and type. */
function resultsExam(array $attributes = []): Exam
{
    $exam = Exam::create([
        'academic_year_id' => test()->sevenA->academic_year_id, 'name' => 'ខែវិច្ឆិកា', 'type' => 'monthly', 'month' => 11,
        'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'status' => ExamStatus::Draft, ...$attributes,
    ]);
    $exam->syncClassrooms([test()->sevenA->id, test()->sevenB->id]);

    return app(TransitionExamStatus::class)->handle($exam, ExamStatus::Open);
}

/** Types every score in: [enrollment id => [KHM, MATH]]. */
function fill(Exam $exam, $classroom, array $scores): void
{
    $cells = [];
    foreach ($scores as $enrollmentId => [$khm, $math]) {
        $cells[] = ['enrollment_id' => $enrollmentId, 'subject_id' => test()->khm->id, 'value' => (string) $khm];
        $cells[] = ['enrollment_id' => $enrollmentId, 'subject_id' => test()->math->id, 'value' => (string) $math];
    }
    app(EnterScores::class)->handle($exam, $classroom, $cells, test()->office);
}

/** Every sheet approved, the exam approved and locked: the results job runs (queue is sync). */
function lockExam(Exam $exam): Exam
{
    ExamSubjectStatus::where('exam_id', $exam->id)->update(['status' => SubjectExamStatus::Approved]);
    test()->actingAs(test()->office)->post("/exams/{$exam->id}/transition", ['status' => 'approved'])->assertSessionHasNoErrors();
    test()->actingAs(test()->office)->post("/exams/{$exam->id}/transition", ['status' => 'locked'])->assertSessionHasNoErrors();

    return $exam->fresh();
}

function standardSheet(): Exam
{
    $exam = resultsExam();
    [$a1, $a2, $a3, $a4] = test()->a;
    [$b1, $b2] = test()->b;
    fill($exam, test()->sevenA, [$a1 => [90, 45], $a2 => [70, 35], $a3 => [70, 'A'], $a4 => [80.5, 24.5]]);
    fill($exam, test()->sevenB, [$b1 => [100, 50], $b2 => [50, 26]]);

    return $exam;
}

it('writes totals, averages, class and grade ranks, mentions and pass on lock', function () {
    $exam = lockExam(standardSheet());

    expect($exam->results_version)->toBe(1);
    $r = ExamResult::where('exam_id', $exam->id)->get()->keyBy('enrollment_id');
    [$a1, $a2, $a3, $a4] = $this->a;
    [$b1, $b2] = $this->b;

    // 7A: 45.00, 35.00 (tie), 23.33, 35.00 (tie); 7B: 50.00, 25.33
    expect($r[$a1]->only('total', 'average', 'rank_class', 'rank_grade', 'mention', 'passed'))
        ->toBe(['total' => '135.00', 'average' => '45.00', 'rank_class' => 1, 'rank_grade' => 2, 'mention' => 'excellent', 'passed' => true])
        ->and([$r[$a2]->rank_class, $r[$a4]->rank_class, $r[$a3]->rank_class])->toBe([2, 2, 4])
        ->and([$r[$a2]->rank_grade, $r[$a4]->rank_grade])->toBe([3, 3])
        ->and($r[$a3]->only('average', 'mention', 'passed', 'absent_count'))->toBe(['average' => '23.33', 'mention' => 'weak', 'passed' => false, 'absent_count' => 1])
        ->and($r[$b1]->only('average', 'rank_class', 'rank_grade'))->toBe(['average' => '50.00', 'rank_class' => 1, 'rank_grade' => 1])
        ->and($r[$b2]->only('average', 'passed', 'rank_grade'))->toBe(['average' => '25.33', 'passed' => true, 'rank_grade' => 5])
        ->and($r[$a3]->rank_grade)->toBe(6);
});

it('keeps an absent student out of the ranking when the school says so', function () {
    $this->school->forceFill(['settings' => array_merge($this->school->settings->toArray(), ['absentSubjectPolicy' => 'exclude_from_rank'])])->save();
    $exam = lockExam(standardSheet());

    $absent = ExamResult::where('exam_id', $exam->id)->where('enrollment_id', $this->a[2])->firstOrFail();
    expect($absent->average)->toBe('23.33')->and($absent->rank_class)->toBeNull()->and($absent->rank_grade)->toBeNull();
});

it('shows live results while the exam is open, with missing cells, and writes nothing', function () {
    $exam = resultsExam();
    fill($exam, $this->sevenA, [$this->a[0] => [90, 45]]);

    $this->actingAs($this->office)->get("/exams/{$exam->id}/results?classroom={$this->sevenA->id}")
        ->assertInertia(fn ($page) => $page->component('Exams/Results')
            ->where('rows.0.average', 45)->where('rows.0.rank_class', 1)
            ->where('rows.1.missing', 2)
            ->where('statistics.1.passed', 1));
    expect(ExamResult::count())->toBe(0);
});

it('will not publish before the results are calculated', function () {
    Bus::fake([CalculateExamResults::class]);
    $exam = lockExam(standardSheet());
    Bus::assertDispatched(CalculateExamResults::class);

    $this->actingAs($this->office)->post("/exams/{$exam->id}/transition", ['status' => 'published'])
        ->assertSessionHasErrors(['status' => __('exams.errors.results_not_ready')]);
});

it('refuses to lock a class with no curriculum', function () {
    CurriculumSubject::query()->delete();
    $exam = resultsExam();
    ExamSubjectStatus::where('exam_id', $exam->id)->update(['status' => SubjectExamStatus::Approved]);
    $exam->update(['status' => ExamStatus::Approved]);

    $this->actingAs($this->office)->post("/exams/{$exam->id}/transition", ['status' => 'locked'])->assertSessionHasErrors('status');
    expect($exam->fresh()->status)->toBe(ExamStatus::Approved);
});

it('corrects a locked score through a request the director approves, as a new version', function () {
    $teacherUser = member($this->school, Role::SubjectTeacher);
    $teacher = Teacher::create(['name_km' => 'គ្រូគណិត', 'gender' => 'male', 'employment_type' => 'contract', 'status' => 'active']);
    $teacher->forceFill(['user_id' => $teacherUser->id])->save();
    TeachingAssignment::create(['academic_year_id' => $this->sevenA->academic_year_id, 'classroom_id' => $this->sevenA->id, 'subject_id' => $this->math->id, 'teacher_id' => $teacher->id]);
    $exam = lockExam(standardSheet());
    $ask = fn (User $user, array $data) => $this->actingAs($user)->post("/exams/{$exam->id}/changes", [
        'classroom_id' => $this->sevenA->id, 'enrollment_id' => $this->a[2], 'subject_id' => $this->math->id, 'reason' => 'បានវត្តមាន តែបាត់ក្រដាស', ...$data,
    ]);

    $ask($teacherUser, ['new_value' => '60'])->assertSessionHasErrors('new_value'); // above max 50
    $ask($teacherUser, ['new_value' => 'A'])->assertSessionHasErrors('new_value'); // same as now
    $ask(member($this->school, Role::SubjectTeacher), ['new_value' => '40'])->assertForbidden(); // not their subject
    $ask($teacherUser, ['new_value' => '40'])->assertSessionHas('success');
    $ask($teacherUser, ['new_value' => '41'])->assertSessionHasErrors('new_value'); // one pending per cell

    $change = ScoreChangeRequest::firstOrFail();
    expect($change->old_value)->toBe('A')->and($change->new_value)->toBe('40');
    $this->actingAs($this->office)->post("/exams/{$exam->id}/changes/{$change->id}/decide", ['approve' => true])->assertForbidden();

    $this->actingAs($this->director)->post("/exams/{$exam->id}/changes/{$change->id}/decide", ['approve' => true])->assertSessionHas('success');

    expect($exam->fresh()->results_version)->toBe(2)
        ->and(Score::where('enrollment_id', $this->a[2])->where('subject_id', $this->math->id)->value('score'))->toBe('40.00');
    $v1 = ExamResult::where('exam_id', $exam->id)->where('enrollment_id', $this->a[2])->where('version', 1)->firstOrFail();
    $v2 = ExamResult::where('exam_id', $exam->id)->where('enrollment_id', $this->a[2])->where('version', 2)->firstOrFail();
    // (70 + 40) / 3 = 36.67: from last in class to first among the 35.00s
    expect($v1->average)->toBe('23.33')->and($v2->average)->toBe('36.67')->and($v2->rank_class)->toBe(2);
});

it('records a rejected change without touching the score', function () {
    $exam = lockExam(standardSheet());
    $this->actingAs($this->office)->post("/exams/{$exam->id}/changes", [
        'classroom_id' => $this->sevenA->id, 'enrollment_id' => $this->a[0], 'subject_id' => $this->khm->id, 'new_value' => '95', 'reason' => 'វាយខុស',
    ])->assertSessionHasNoErrors();
    $change = ScoreChangeRequest::firstOrFail();

    $this->actingAs($this->director)->post("/exams/{$exam->id}/changes/{$change->id}/decide", ['approve' => false, 'note' => 'ក្រដាសសរសេរ 90'])->assertSessionHasNoErrors();

    expect($change->fresh()->status->value)->toBe('rejected')
        ->and($exam->fresh()->results_version)->toBe(1)
        ->and(Score::where('enrollment_id', $this->a[0])->where('subject_id', $this->khm->id)->value('score'))->toBe('90.00');
});

it('shows the grade ranking to managers once locked', function () {
    $exam = lockExam(standardSheet());

    $this->actingAs($this->office)->get("/exams/{$exam->id}/results/grade?grade={$this->sevenA->grade_level_id}")
        ->assertInertia(fn ($page) => $page->component('Exams/GradeResults')
            ->where('rows.0.rank_grade', 1)->where('rows.0.classroom', '7B')->has('rows', 6));
    $this->actingAs(member($this->school, Role::SubjectTeacher))->get("/exams/{$exam->id}/results/grade?grade={$this->sevenA->grade_level_id}")->assertForbidden();
});

it('works out semester results from monthly exams and the semester exam', function () {
    $s1 = Term::where('academic_year_id', $this->sevenA->academic_year_id)->orderBy('sort')->firstOrFail();
    $dara = $this->a[3];

    // Two monthly exams (averages 35.00 and 40.00) and the semester exam (30.00), for one student.
    foreach ([[[80.5, 24.5], 'monthly'], [[90, 30], 'monthly'], [[60, 30], 'semester']] as $i => [$scores, $type]) {
        $exam = resultsExam(['term_id' => $s1->id, 'type' => $type, 'name' => "S1-{$i}", 'end_date' => '2026-12-0'.($i + 1)]);
        fill($exam, $this->sevenA, [$dara => $scores]);
        foreach (array_slice($this->a, 0, 3) as $other) {
            fill($exam, $this->sevenA, [$other => [50, 25]]);
        }
        fill($exam, $this->sevenB, [$this->b[0] => [50, 25], $this->b[1] => [50, 25]]);
        lockExam($exam);
    }

    // monthly mean 37.50; semester = 0.5 × 37.50 + 0.5 × 30.00 = 33.75. The year has only S1, so it is 33.75 too.
    $this->actingAs($this->office)->get("/results/classes/{$this->sevenA->id}?term={$s1->id}")
        ->assertInertia(fn ($page) => $page->component('Exams/PeriodResults')
            // Props travel as JSON, where 30.0 comes back as 30: compare by value.
            ->where('rows', fn ($rows) => collect($rows)->firstWhere('enrollment_id', $dara)['monthly_average'] == 37.5
                && collect($rows)->firstWhere('enrollment_id', $dara)['exam_average'] == 30
                && collect($rows)->firstWhere('enrollment_id', $dara)['average'] == 33.75
                && collect($rows)->firstWhere('enrollment_id', $dara)['rank'] === 1));
    $this->actingAs($this->office)->get("/results/classes/{$this->sevenA->id}")
        ->assertInertia(fn ($page) => $page->where('rows', fn ($rows) => collect($rows)->firstWhere('enrollment_id', $dara)['average'] == 33.75));
});

it('lists a student\'s exam results on their profile', function () {
    $exam = lockExam(standardSheet());
    $student = Enrollment::findOrFail($this->a[0])->student_id;

    $this->actingAs($this->director)->get("/people/students/{$student}")
        ->assertInertia(fn ($page) => $page->where('results.0.exam_id', $exam->id)->where('results.0.average', 45)->where('results.0.mention', 'excellent'));
});

it('keeps results away from people who do not work on the class', function () {
    $exam = lockExam(standardSheet());

    $this->actingAs(member($this->school, Role::Guardian))->get("/exams/{$exam->id}/results?classroom={$this->sevenA->id}")->assertForbidden();
    $this->actingAs(member($this->school, Role::Registrar))->get("/results/classes/{$this->sevenA->id}")->assertForbidden();
});
