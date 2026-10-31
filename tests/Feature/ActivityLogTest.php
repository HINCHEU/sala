<?php

use App\Domain\Academic\Models\Subject;
use App\Domain\People\Actions\EnrollStudents;
use App\Domain\People\Models\Enrollment;
use App\Domain\School\Enums\Role;
use App\Support\Activity\SchoolActivity;
use App\Support\Tenancy\CurrentSchool;

beforeEach(function () {
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
    $this->class = classesFor($this->school, ['7A'])['7A'];
});

it('stamps each event with the current school', function () {
    activity()->log('exam.created');

    expect(SchoolActivity::latest('id')->first()->school_id)->toBe($this->school->id);
});

it('shows who changed which score from what to what', function () {
    $student = makeStudent('កែវ', 'មុនី');
    app(EnrollStudents::class)->handle($this->class, [$student->id]);
    $subject = Subject::where('code', 'KHM')->firstOrFail();
    activity()->causedBy($this->director)->withProperties([
        'enrollment_id' => Enrollment::firstOrFail()->id, 'subject_id' => $subject->id, 'old' => '10', 'new' => '12',
    ])->log('score.entered');

    $this->actingAs($this->director)->get('/activity?group=scores')->assertInertia(fn ($page) => $page
        ->component('School/Activity')
        ->has('events.data', 1)
        ->where('events.data.0.who', $this->director->name)
        ->where('events.data.0.event', __('activity.events.score.entered'))
        ->where('events.data.0.person', 'កែវ មុនី')
        ->where('events.data.0.subject', $subject->name_km)
        ->where('events.data.0.old', '10')
        ->where('events.data.0.new', '12'));
});

it('does not repeat whole attribute arrays from model events', function () {
    activity()->withProperties(['old' => ['report_header' => 'x'], 'attributes' => ['report_header' => 'y']])->log('updated');

    $this->actingAs($this->director)->get('/activity')->assertInertia(fn ($page) => $page
        ->where('events.data.0.event', 'updated')
        ->where('events.data.0.old', null));
});

it('filters by type, user and date', function () {
    activity()->causedBy($this->director)->log('exam.created');
    activity()->log('score.entered');
    $old = activity()->log('member.added');
    $old->created_at = '2020-01-01 10:00:00';
    $old->save();
    $baseline = SchoolActivity::where('school_id', $this->school->id)->count();

    $count = fn (string $query) => $this->actingAs($this->director)->get("/activity?{$query}")->inertiaPage()['props']['events']['total'];

    expect($count('group=exams'))->toBe(1)
        ->and($count('user='.$this->director->id))->toBeGreaterThanOrEqual(1)
        ->and($count('to=2020-12-31'))->toBe(1)
        ->and($count(''))->toBe($baseline);
});

it('hides another school\'s events', function () {
    activity()->log('exam.created');
    $otherSchool = makeSchool();
    app(CurrentSchool::class)->set($otherSchool);
    activity()->log('member.invited');
    app(CurrentSchool::class)->set(null);

    $page = $this->actingAs($this->director)->get('/activity')->inertiaPage();

    expect(collect($page['props']['events']['data'])->pluck('event'))
        ->toContain(__('activity.events.exam.created'))
        ->not->toContain(__('activity.events.member.invited'));
});

it('has a label in both languages for every event the code logs', function () {
    $events = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if ($file->getExtension() === 'php' && preg_match_all("/->log\((.+?)\);/s", file_get_contents($file->getPathname()), $m)) {
            foreach ($m[1] as $argument) {
                preg_match_all("/'([a-z_]+\.[a-z_]+)'/", $argument, $names);
                array_push($events, ...$names[1]);
            }
        }
    }

    expect($events)->not->toBeEmpty();
    foreach (array_unique($events) as $event) {
        foreach (['km', 'en'] as $locale) {
            expect(trans()->hasForLocale("activity.events.{$event}", $locale))->toBeTrue("{$event} has no {$locale} label");
        }
    }
});
