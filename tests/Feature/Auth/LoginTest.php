<?php

use App\Models\User;

it('sends guests to the login page', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('signs in by email', function () {
    $user = User::factory()->create(['email' => 'teacher@sala.test']);

    $this->post('/login', ['login' => 'Teacher@Sala.test', 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('signs in by phone written any common way', function (string $typed) {
    $user = User::factory()->create(['email' => null, 'phone' => '+85512345678']);

    $this->post('/login', ['login' => $typed, 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
})->with(['012 345 678', '+855 12 345 678', '85512345678']);

it('rejects a wrong password with the Khmer message', function () {
    $user = User::factory()->create();

    $this->from('/login')
        ->post('/login', ['login' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => __('auth.failed', [], 'km')]);
    $this->assertGuest();
});

it('throttles repeated attempts', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['login' => 'nobody@sala.test', 'password' => 'x']);
    }

    $this->post('/login', ['login' => 'nobody@sala.test', 'password' => 'x'])
        ->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toContain('វិនាទី');
});

it('signs out', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
