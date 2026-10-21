<?php

use App\Models\User;

it('shows the login page to guests', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('signs a user in and out', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});

it('rejects a wrong password with a Khmer message', function () {
    $user = User::factory()->create();

    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => __('auth.failed', [], 'km')]);
    $this->assertGuest();
});

it('throttles repeated login attempts', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'nobody@sala.test', 'password' => 'x']);
    }

    $this->post('/login', ['email' => 'nobody@sala.test', 'password' => 'x'])->assertStatus(429);
});
