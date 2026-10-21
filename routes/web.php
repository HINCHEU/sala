<?php

use App\Domain\School\Enums\Permission;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Platform\SchoolController;
use Illuminate\Support\Facades\Route;

// Login, logout, password reset and the 2FA challenge come from Fortify (FortifyServiceProvider).

Route::post('/locale', [AccountController::class, 'locale'])->name('locale.update');

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('throttle:10,1')->name('invitations.accept');

Route::middleware('auth')->group(function () {
    // Reachable before 2FA is set up, so a director can turn it on.
    Route::get('/security', [AccountController::class, 'security'])->name('security.show');
    Route::post('/schools/switch', [AccountController::class, 'switchSchool'])->name('schools.switch');

    Route::middleware('two-factor.required')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');

        Route::middleware('can:platform')->prefix('platform')->name('platform.')->group(function () {
            Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
            Route::get('/schools/create', [SchoolController::class, 'create'])->name('schools.create');
            Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
            Route::post('/schools/{school}/invitations', [SchoolController::class, 'invite'])->name('schools.invite');
        });

        Route::middleware('school')->group(function () {
            Route::middleware('can:'.Permission::ManageSettings->value)->group(function () {
                Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
                Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            });

            Route::middleware('can:'.Permission::ManageMembers->value)->group(function () {
                Route::get('/members', [MemberController::class, 'index'])->name('members.index');
                Route::post('/members/invitations', [MemberController::class, 'invite'])->name('members.invite');
                Route::post('/members/{member}/password', [MemberController::class, 'resetPassword'])->name('members.password');
            });
        });
    });
});
