<?php

use App\Domain\People\Models\Student;
use App\Domain\School\Enums\Role;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->school = makeSchool();
    $this->registrar = member($this->school, Role::Registrar);
    app(CurrentSchool::class)->set($this->school);
});

$valid = fn (array $overrides = []) => [
    'last_name_km' => 'សុខ', 'first_name_km' => 'ដារ៉ា', 'last_name_en' => 'Sok', 'first_name_en' => 'Dara',
    'gender' => 'female', 'dob' => '2014-03-15', 'status' => 'active', 'birth_province' => 'កំពត', ...$overrides,
];

it('numbers new students from the highest numeric code, skipping codes the school typed', function () use ($valid) {
    makeStudent(attributes: ['student_code' => '00041']);
    makeStudent(attributes: ['student_code' => '2024-A9']);

    $this->actingAs($this->registrar)->post('/people/students', $valid())->assertSessionHasNoErrors();
    $this->actingAs($this->registrar)->post('/people/students', $valid(['first_name_km' => 'ចាន់']))->assertSessionHasNoErrors();

    expect(Student::latest('id')->limit(2)->pluck('student_code')->all())->toBe(['00043', '00042']);
});

it('keeps student codes unique within a school only', function () use ($valid) {
    $this->actingAs($this->registrar)->post('/people/students', $valid(['student_code' => 'A1']))->assertSessionHasNoErrors();
    $this->actingAs($this->registrar)->post('/people/students', $valid(['student_code' => 'A1']))->assertSessionHasErrors('student_code');

    $other = makeSchool();
    app(CurrentSchool::class)->set($other);
    $this->actingAs(member($other, Role::Registrar))->post('/people/students', $valid(['student_code' => 'A1']))->assertSessionHasNoErrors();
});

it('encrypts the sensitive fields at rest and shows them on the profile', function () use ($valid) {
    $this->actingAs($this->registrar)->post('/people/students', $valid([
        'birth_cert_no' => 'BC-12345', 'id_poor_level' => '1', 'disability' => 'ខ្សោយភ្នែក',
    ]))->assertSessionHasNoErrors();

    $student = Student::firstOrFail();
    $raw = DB::table('students')->where('id', $student->id)->first();
    expect($raw->birth_cert_no)->not->toContain('BC-12345')
        ->and($raw->disability)->not->toContain('ខ្សោយ')
        ->and($student->birth_cert_no)->toBe('BC-12345');

    $this->actingAs($this->registrar)->get("/people/students/{$student->id}")
        ->assertInertia(fn ($page) => $page
            ->where('student.name_km', 'សុខ ដារ៉ា')
            ->where('student.id_poor_level', '1')
            ->where('student.birth_cert_no', 'BC-12345'));
});

it('re-encodes the photo as a JPEG on the private disk', function () use ($valid) {
    Storage::fake('media_private');

    $this->actingAs($this->registrar)->post('/people/students', $valid([
        'photo' => UploadedFile::fake()->image('phone.png', 1200, 1600),
    ]))->assertSessionHasNoErrors();

    $photo = Student::firstOrFail()->photo;
    expect($photo->disk)->toBe('media_private')->and($photo->mime)->toBe('image/jpeg');
    Storage::disk('media_private')->assertExists($photo->path);
    [$width, $height] = getimagesizefromstring(Storage::disk('media_private')->get($photo->path));
    expect(max($width, $height))->toBeLessThanOrEqual(600);
});

it('refuses a province that is not one of the 25 and a missing Khmer name', function () use ($valid) {
    $this->actingAs($this->registrar)->post('/people/students', $valid(['birth_province' => 'Atlantis', 'first_name_km' => '']))
        ->assertSessionHasErrors(['birth_province', 'first_name_km']);
});

it('finds students by Khmer or Latin name, code, or a guardian phone typed any way', function () {
    $dara = makeStudent('សុខ', 'ដារ៉ា', ['last_name_en' => 'Sok', 'first_name_en' => 'Dara', 'student_code' => '00077']);
    makeStudent('ចាន់', 'សុភា');
    $dara->guardians()->create(['name_km' => 'សុខ ចិន្ដា', 'phone' => '+85512345678'], [
        'school_id' => $this->school->id, 'relationship' => 'father',
    ]);

    $search = fn (string $q) => $this->actingAs($this->registrar)->get('/people/students?q='.urlencode($q))
        ->assertOk()->viewData('page')['props']['students']['data'];

    expect(collect($search('សុខ ដារ៉ា'))->pluck('id')->all())->toBe([$dara->id])
        ->and(collect($search('dara'))->pluck('id')->all())->toBe([$dara->id])
        ->and(collect($search('00077'))->pluck('id')->all())->toBe([$dara->id])
        ->and(collect($search('012 345 678'))->pluck('id')->all())->toBe([$dara->id])
        ->and($search('នរណា'))->toBe([]);
});

it('updates a student without changing an existing code', function () use ($valid) {
    $student = makeStudent(attributes: ['student_code' => '00009']);

    $this->actingAs($this->registrar)->put("/people/students/{$student->id}", $valid(['student_code' => '', 'first_name_km' => 'វិចិត្រ']))
        ->assertRedirect("/people/students/{$student->id}");

    expect($student->fresh())->student_code->toBe('00009')->first_name_km->toBe('វិចិត្រ');
});
