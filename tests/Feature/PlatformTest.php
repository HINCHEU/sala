<?php

use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Spatie\Permission\Models\Role as RoleModel;

it('creates a school with its roles and a director invitation link', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/platform/schools', [
        'code' => 'kampot-high',
        'name_km' => 'វិទ្យាល័យកំពត',
        'name_en' => 'Kampot High School',
        'type' => 'public',
        'stage' => 'secondary',
        'province' => 'កំពត',
        'phone' => '097 123 4567',
    ])->assertRedirect('/platform/schools')->assertSessionHas('invite_link');

    $school = School::where('code', 'kampot-high')->firstOrFail();
    expect(RoleModel::where('school_id', $school->id)->count())->toBe(8);

    auth()->logout();
    $this->post(session('invite_link'), ['name' => 'នាយក', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);

    $director = User::where('phone', '+855971234567')->firstOrFail();
    app(CurrentSchool::class)->set($school);
    expect($director->hasRole('director'))->toBeTrue();
});

it('validates the school code as a subdomain', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/platform/schools', ['code' => 'Bad Code!', 'email' => 'x@sala.test'])
        ->assertSessionHasErrors(['code', 'name_km', 'name_en', 'type', 'stage', 'province']);
});

it('changes language for guests and remembers it for users', function () {
    $this->post('/locale', ['locale' => 'en']);
    $this->get('/login')->assertInertia(fn ($page) => $page->where('locale', 'en'));

    $user = User::factory()->create();
    $this->actingAs($user)->post('/locale', ['locale' => 'km']);
    expect($user->fresh()->locale)->toBe('km');
});
