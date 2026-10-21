<?php

use App\Domain\School\Enums\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media_public');
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
});

function validSettings(array $overrides = []): array
{
    return [
        'name_km' => 'សាលាថ្មី',
        'name_en' => 'New School',
        'report_header' => 'ក្រសួងអប់រំ យុវជន និងកីឡា',
        'brand_color' => '#0f766e',
        'settings' => [
            'locale' => 'km',
            'averageMethod' => 'divisor',
            'semesterMonthlyWeight' => '0.4',
            'semesterExamWeight' => '0.6',
            'annualS1Weight' => '0.5',
            'annualS2Weight' => '0.5',
            'absentSubjectPolicy' => 'exclude_from_rank',
            'rankAcrossGrade' => '0',
            'scoreApproverRole' => 'academic_office',
            'attendanceMode' => 'both',
            'khmerNumerals' => '1',
        ],
        ...$overrides,
    ];
}

it('saves the profile, rules and a re-encoded logo', function () {
    $this->actingAs($this->director)
        ->post('/settings', validSettings(['logo' => UploadedFile::fake()->image('logo.jpg', 1200, 800)]))
        ->assertSessionHasNoErrors();

    $school = $this->school->fresh(['logo']);
    expect($school->name_en)->toBe('New School')
        ->and($school->brand_color_soft)->toBe('#e2efee')
        ->and($school->settings->semesterExamWeight)->toBe(0.6)
        ->and($school->settings->rankAcrossGrade)->toBeFalse()
        ->and($school->settings->khmerNumerals)->toBeTrue()
        ->and($school->settings->scoreApproverRole)->toBe('academic_office')
        ->and($school->logo->mime)->toBe('image/png');

    Storage::disk('media_public')->assertExists($school->logo->path);
    [$w, $h] = getimagesizefromstring(Storage::disk('media_public')->get($school->logo->path));
    expect(max($w, $h))->toBe(512);
});

it('rejects a brand colour white text cannot be read on, and suggests a darker one', function () {
    $this->actingAs($this->director)
        ->post('/settings', validSettings(['brand_color' => '#e9b949']))
        ->assertSessionHasErrors('brand_color');

    expect(session('errors')->first('brand_color'))->toMatch('/#[0-9a-f]{6}/');
});

it('requires each pair of weights to add up to 1', function () {
    $this->actingAs($this->director)
        ->post('/settings', validSettings(['settings' => [...validSettings()['settings'], 'semesterExamWeight' => '0.7']]))
        ->assertSessionHasErrors('settings.semesterMonthlyWeight');
});

it('rejects SVG logos', function () {
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $this->actingAs($this->director)
        ->post('/settings', validSettings(['logo' => $svg]))
        ->assertSessionHasErrors('logo');
});

it('keeps settings this page does not edit', function () {
    $this->school->update(['settings' => [...$this->school->settings->toArray(), 'modules' => ['finance' => true]]]);

    $this->actingAs($this->director)->post('/settings', validSettings())->assertSessionHasNoErrors();

    expect($this->school->fresh()->settings->modules)->toBe(['finance' => true]);
});

it('switches the interface to the school language for users without a choice', function () {
    $this->actingAs($this->director)->post('/settings', validSettings(['settings' => [...validSettings()['settings'], 'locale' => 'en']]));

    $this->actingAs($this->director)->get('/members')->assertInertia(fn ($page) => $page->where('locale', 'en'));
});
