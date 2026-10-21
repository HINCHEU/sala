<?php

use App\Domain\Academic\Actions\ApplyMoeysTemplate;
use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\GradingScale;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Track;
use App\Domain\School\Enums\Role;
use App\Support\Tenancy\CurrentSchool;

beforeEach(function () {
    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    app(CurrentSchool::class)->set($this->school);
});

it('applies the MoEYS template once, without overwriting edits', function () {
    $this->actingAs($this->office)->post('/academic/structure/moeys-template')->assertSessionHas('success');

    expect(GradeLevel::count())->toBe(12)
        ->and(GradeLevel::where('number', 11)->value('has_tracks'))->toBeTrue()
        ->and(GradeLevel::where('number', 7)->value('name_km'))->toBe('ថ្នាក់ទី៧')
        ->and(Track::count())->toBe(2)
        ->and(Subject::count())->toBeGreaterThan(10)
        ->and(GradingScale::count())->toBe(3);

    Subject::where('code', 'KHM')->update(['name_km' => 'អក្សរសាស្ត្រខ្មែរ']);
    app(ApplyMoeysTemplate::class)->handle();

    expect(GradeLevel::count())->toBe(12)
        ->and(Subject::where('code', 'KHM')->value('name_km'))->toBe('អក្សរសាស្ត្រខ្មែរ');
});

it('makes the first year current, with two semesters, and keeps exactly one current', function () {
    $this->actingAs($this->office)->post('/academic/years', ['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $this->actingAs($this->office)->post('/academic/years', ['name' => '2027–2028', 'start_date' => '2027-11-01', 'end_date' => '2028-08-31']);

    $first = AcademicYear::where('name', '2026–2027')->firstOrFail();
    $second = AcademicYear::where('name', '2027–2028')->firstOrFail();
    expect($first->is_current)->toBeTrue()
        ->and($second->is_current)->toBeFalse()
        ->and($first->terms()->pluck('name_en')->all())->toBe(['Semester 1', 'Semester 2']);

    $this->actingAs($this->office)->post("/academic/years/{$second->id}/current");
    expect(AcademicYear::where('is_current', true)->pluck('id')->all())->toBe([$second->id]);

    $this->actingAs($this->office)->delete("/academic/years/{$second->id}")->assertSessionHas('error');
    expect(AcademicYear::find($second->id))->not->toBeNull();
});

it('blocks deleting a subject that a curriculum uses, and deletes an unused one', function () {
    app(ApplyMoeysTemplate::class)->handle();
    $year = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $math = Subject::where('code', 'MATH')->firstOrFail();
    $french = Subject::where('code', 'FRA')->firstOrFail();
    CurriculumSubject::create([
        'academic_year_id' => $year->id, 'grade_level_id' => GradeLevel::where('number', 7)->value('id'),
        'subject_id' => $math->id, 'max_score' => 50, 'coefficient' => 1, 'pass_score' => 25,
    ]);

    $this->actingAs($this->office)->delete("/academic/subjects/{$math->id}")->assertSessionHas('error', __('academic.in_use'));
    expect(Subject::find($math->id))->not->toBeNull();

    $this->actingAs($this->office)->delete("/academic/subjects/{$french->id}")->assertSessionHas('success');
    expect(Subject::find($french->id))->toBeNull();
});

it('allows the same class name in different years but not twice in one year', function () {
    app(ApplyMoeysTemplate::class)->handle();
    $y1 = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $y2 = app(CreateAcademicYear::class)->handle(['name' => '2027–2028', 'start_date' => '2027-11-01', 'end_date' => '2028-08-31']);
    $grade7 = GradeLevel::where('number', 7)->value('id');
    $class = fn ($year) => ['academic_year_id' => $year->id, 'grade_level_id' => $grade7, 'name' => '7A', 'shift' => 'morning'];

    $this->actingAs($this->office)->post('/academic/classrooms', $class($y1))->assertSessionHasNoErrors();
    $this->actingAs($this->office)->post('/academic/classrooms', $class($y2))->assertSessionHasNoErrors();
    $this->actingAs($this->office)->post('/academic/classrooms', $class($y1))->assertSessionHasErrors('name');

    expect(Classroom::count())->toBe(2);
});

it('requires a track for grades that have tracks, and refuses one where they do not', function () {
    app(ApplyMoeysTemplate::class)->handle();
    $year = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $base = ['academic_year_id' => $year->id, 'shift' => 'morning'];

    $this->actingAs($this->office)->post('/academic/classrooms', [...$base, 'name' => '12A', 'grade_level_id' => GradeLevel::where('number', 12)->value('id')])
        ->assertSessionHasErrors('track_id');
    $this->actingAs($this->office)->post('/academic/classrooms', [
        ...$base, 'name' => '7A', 'grade_level_id' => GradeLevel::where('number', 7)->value('id'), 'track_id' => Track::value('id'),
    ])->assertSessionHasErrors('track_id');
});

it('rejects overlapping mention bands and saves valid ones best first', function () {
    $this->actingAs($this->office)->put('/academic/grading/primary', ['bands' => [
        ['min' => 0, 'max' => 6, 'mention' => 'weak', 'mention_km' => 'ខ្សោយ', 'mention_en' => 'Weak', 'letter' => 'F'],
        ['min' => 5, 'max' => 10, 'mention' => 'good', 'mention_km' => 'ល្អ', 'mention_en' => 'Good', 'letter' => 'C'],
    ]])->assertSessionHasErrors('bands');

    $this->actingAs($this->office)->put('/academic/grading/primary', ['bands' => [
        ['min' => 0, 'max' => 5, 'mention' => 'weak', 'mention_km' => 'ខ្សោយ', 'mention_en' => 'Weak', 'letter' => 'F'],
        ['min' => 5, 'max' => 10, 'mention' => 'good', 'mention_km' => 'ល្អ', 'mention_en' => 'Good', 'letter' => 'C'],
    ]])->assertSessionHasNoErrors();

    expect(array_column(GradingScale::where('stage', 'primary')->firstOrFail()->bands, 'mention'))->toBe(['good', 'weak']);
});
