<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds demo accounts that sign in with "password"', function () {
    Storage::fake('media_public');
    Storage::fake('media_private');

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@sala.test')->firstOrFail();
    expect($admin->is_platform_admin)->toBeTrue();

    $this->post('/login', ['login' => 'admin@sala.test', 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($admin);
    expect(User::where('email', 'director@demo1.sala.test')->exists())->toBeTrue();
});
