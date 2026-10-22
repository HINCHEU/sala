<?php

use App\Domain\People\Models\Guardian;
use App\Domain\School\Enums\Role;
use App\Support\Tenancy\CurrentSchool;

beforeEach(function () {
    $this->school = makeSchool();
    $this->registrar = member($this->school, Role::Registrar);
    app(CurrentSchool::class)->set($this->school);
    $this->dara = makeStudent('សុខ', 'ដារ៉ា');
    $this->bopha = makeStudent('សុខ', 'បុប្ផា');
});

$father = ['phone' => '012 345 678', 'name_km' => 'សុខ ចិន្ដា', 'relationship' => 'father'];

it('links siblings to one guardian when the phone is already known, typed any way', function () use ($father) {
    $this->actingAs($this->registrar)->post("/people/students/{$this->dara->id}/guardians", $father)->assertSessionHasNoErrors();
    $this->actingAs($this->registrar)->post("/people/students/{$this->bopha->id}/guardians", [
        'phone' => '+855 12 345 678', 'relationship' => 'father', // no name: the known guardian is linked
    ])->assertSessionHas('success', __('people.guardian_linked_existing'));

    expect(Guardian::count())->toBe(1)
        ->and(Guardian::firstOrFail()->students()->pluck('first_name_km')->sort()->values()->all())->toBe(['ដារ៉ា', 'បុប្ផា']);
});

it('asks for a name for a new guardian and refuses a phone that is not Cambodian', function () {
    $this->actingAs($this->registrar)->post("/people/students/{$this->dara->id}/guardians", ['phone' => '012 999 888', 'relationship' => 'mother'])
        ->assertSessionHasErrors('name_km');
    $this->actingAs($this->registrar)->post("/people/students/{$this->dara->id}/guardians", ['phone' => '123', 'name_km' => 'x', 'relationship' => 'mother'])
        ->assertSessionHasErrors('phone');

    expect(Guardian::count())->toBe(0);
});

it('makes the first guardian the primary contact, and keeps one primary', function () use ($father) {
    $url = "/people/students/{$this->dara->id}/guardians";
    $this->actingAs($this->registrar)->post($url, $father);
    $this->actingAs($this->registrar)->post($url, ['phone' => '097 111 2222', 'name_km' => 'កែវ ស្រីមុំ', 'relationship' => 'mother', 'is_primary_contact' => true]);

    $primary = fn () => $this->dara->guardians()->wherePivot('is_primary_contact', true)->pluck('name_km')->all();
    expect($primary())->toBe(['កែវ ស្រីមុំ']);
});

it('will not let an edit take another guardian\'s phone', function () use ($father) {
    $url = "/people/students/{$this->dara->id}/guardians";
    $this->actingAs($this->registrar)->post($url, $father);
    $this->actingAs($this->registrar)->post($url, ['phone' => '097 111 2222', 'name_km' => 'កែវ ស្រីមុំ', 'relationship' => 'mother']);
    $mother = Guardian::where('name_km', 'កែវ ស្រីមុំ')->firstOrFail();

    $this->actingAs($this->registrar)->put("{$url}/{$mother->id}", ['phone' => '012345678', 'name_km' => 'កែវ ស្រីមុំ', 'relationship' => 'mother'])
        ->assertSessionHasErrors('phone');
    expect($mother->fresh()->phone)->toBe('+855971112222');
});

it('unlinks a guardian without deleting them', function () use ($father) {
    $this->actingAs($this->registrar)->post("/people/students/{$this->dara->id}/guardians", $father);
    $guardian = Guardian::firstOrFail();

    $this->actingAs($this->registrar)->delete("/people/students/{$this->dara->id}/guardians/{$guardian->id}")->assertSessionHas('success');

    expect($this->dara->guardians()->count())->toBe(0)->and(Guardian::find($guardian->id))->not->toBeNull();
    $this->actingAs($this->registrar)->delete("/people/students/{$this->bopha->id}/guardians/{$guardian->id}")->assertNotFound();
});

it('looks a phone up within the school only', function () use ($father) {
    $this->actingAs($this->registrar)->post("/people/students/{$this->dara->id}/guardians", $father);

    $this->actingAs($this->registrar)->getJson('/people/guardians/lookup?phone=012345678')
        ->assertJsonPath('guardian.name_km', 'សុខ ចិន្ដា')
        ->assertJsonPath('guardian.children', ['សុខ ដារ៉ា']);

    $other = makeSchool();
    $this->actingAs(member($other, Role::Registrar))->withSession(['school_id' => $other->id])
        ->getJson('/people/guardians/lookup?phone=012345678')
        ->assertJsonPath('guardian', null);
});
