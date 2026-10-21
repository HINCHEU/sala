<?php

use App\Domain\Academic\Actions\ApplyMoeysTemplate;
use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Track;
use App\Domain\School\Enums\Role;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    app(CurrentSchool::class)->set($this->school);
    app(ApplyMoeysTemplate::class)->handle();

    $this->y1 = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $this->y2 = app(CreateAcademicYear::class)->handle(['name' => '2027–2028', 'start_date' => '2027-11-01', 'end_date' => '2028-08-31']);
    $this->grade7 = GradeLevel::where('number', 7)->value('id');
    $this->khmer = Subject::where('code', 'KHM')->value('id');
    $this->math = Subject::where('code', 'MATH')->value('id');
});

function curriculum($test, $year, array $rows, $divisor = 15, array $extra = []): TestResponse
{
    return $test->actingAs($test->office)->put('/academic/curriculum', [
        'academic_year_id' => $year->id,
        'grade_level_id' => $test->grade7,
        'track_id' => null,
        'divisor' => $divisor,
        'rows' => $rows,
        ...$extra,
    ]);
}

function row(int $subjectId, float $max, float $pass, int $sort = 1): array
{
    return ['subject_id' => $subjectId, 'max_score' => $max, 'coefficient' => 1, 'pass_score' => $pass, 'sort' => $sort];
}

it('saves a grade\'s curriculum and divisor, replacing what was there', function () {
    curriculum($this, $this->y1, [row($this->khmer, 50, 25, 1), row($this->math, 50, 25, 2)])->assertSessionHasNoErrors();
    curriculum($this, $this->y1, [row($this->math, 100, 50)], 10)->assertSessionHasNoErrors();

    $rows = CurriculumSubject::where('academic_year_id', $this->y1->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->subject_id)->toBe($this->math)
        ->and($rows->first()->max_score)->toBe('100.00')
        ->and(GradeDivisor::where('academic_year_id', $this->y1->id)->value('divisor'))->toBe('10.00');
});

it('keeps each year\'s curriculum independent after copying', function () {
    curriculum($this, $this->y1, [row($this->khmer, 50, 25)])->assertSessionHasNoErrors();

    $this->actingAs($this->office)
        ->post('/academic/curriculum/copy', ['from_year_id' => $this->y1->id, 'to_year_id' => $this->y2->id])
        ->assertSessionHas('success');
    expect(CurriculumSubject::where('academic_year_id', $this->y2->id)->value('max_score'))->toBe('50.00');

    curriculum($this, $this->y2, [row($this->khmer, 60, 30)])->assertSessionHasNoErrors();

    expect(CurriculumSubject::where('academic_year_id', $this->y1->id)->value('max_score'))->toBe('50.00')
        ->and(CurriculumSubject::where('academic_year_id', $this->y2->id)->value('max_score'))->toBe('60.00')
        ->and(GradeDivisor::where('academic_year_id', $this->y1->id)->count())->toBe(1);
});

it('rejects a pass score above the max, duplicate subjects and a missing track', function () {
    curriculum($this, $this->y1, [row($this->khmer, 50, 60)])->assertSessionHasErrors('rows.0.pass_score');
    curriculum($this, $this->y1, [row($this->khmer, 50, 25), row($this->khmer, 50, 25)])->assertSessionHasErrors('rows.0.subject_id');

    $grade12 = GradeLevel::where('number', 12)->value('id');
    curriculum($this, $this->y1, [row($this->math, 50, 25)], extra: ['grade_level_id' => $grade12])->assertSessionHasErrors('track_id');
    curriculum($this, $this->y1, [row($this->math, 50, 25)], extra: ['grade_level_id' => $grade12, 'track_id' => Track::value('id')])
        ->assertSessionHasNoErrors();
});

it('refuses another school\'s subject in the curriculum', function () {
    $other = makeSchool();
    $foreignSubject = app(CurrentSchool::class)->runAs($other, fn () => Subject::create([
        'code' => 'X', 'name_km' => 'X', 'name_en' => 'X', 'category' => 'other',
    ]))->id;

    curriculum($this, $this->y1, [row($foreignSubject, 50, 25)])->assertSessionHasErrors('rows.0.subject_id');
});

it('shows the saved grid for the chosen year and grade', function () {
    curriculum($this, $this->y1, [row($this->math, 50, 25)])->assertSessionHasNoErrors();

    $this->actingAs($this->office)->get("/academic/curriculum?year={$this->y1->id}&grade={$this->grade7}")
        ->assertInertia(fn ($page) => $page
            ->component('Academic/Curriculum')
            ->where('selection.grade', $this->grade7)
            ->where('rows.0.subject_id', $this->math)
            ->where('divisor', '15.00'));
});
