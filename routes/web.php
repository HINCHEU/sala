<?php

use App\Domain\School\Enums\Permission;
use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\ClassroomController;
use App\Http\Controllers\Academic\CurriculumController;
use App\Http\Controllers\Academic\GradeLevelController;
use App\Http\Controllers\Academic\GradingScaleController;
use App\Http\Controllers\Academic\RoomController;
use App\Http\Controllers\Academic\StructureController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\TrackController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\People\EnrollmentController;
use App\Http\Controllers\People\GuardianController;
use App\Http\Controllers\People\StudentController;
use App\Http\Controllers\People\StudentDocumentController;
use App\Http\Controllers\People\StudentGuardianController;
use App\Http\Controllers\People\StudentImportController;
use App\Http\Controllers\People\TeacherController;
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

            Route::middleware('can:'.Permission::ManageAcademic->value)->prefix('academic')->name('academic.')->group(function () {
                Route::get('/years', [AcademicYearController::class, 'index'])->name('years.index');
                Route::post('/years', [AcademicYearController::class, 'store'])->name('years.store');
                Route::put('/years/{year}', [AcademicYearController::class, 'update'])->name('years.update');
                Route::delete('/years/{year}', [AcademicYearController::class, 'destroy'])->name('years.destroy');
                Route::post('/years/{year}/current', [AcademicYearController::class, 'setCurrent'])->name('years.current');
                Route::post('/years/{year}/terms', [AcademicYearController::class, 'storeTerm'])->name('terms.store');
                Route::put('/terms/{term}', [AcademicYearController::class, 'updateTerm'])->name('terms.update');
                Route::delete('/terms/{term}', [AcademicYearController::class, 'destroyTerm'])->name('terms.destroy');

                Route::get('/structure', [StructureController::class, 'index'])->name('structure');
                Route::post('/structure/moeys-template', [StructureController::class, 'applyTemplate'])->name('template');
                Route::resource('grades', GradeLevelController::class)->only(['store', 'update', 'destroy']);
                Route::resource('tracks', TrackController::class)->only(['store', 'update', 'destroy']);
                Route::resource('subjects', SubjectController::class)->only(['store', 'update', 'destroy']);
                Route::resource('rooms', RoomController::class)->only(['store', 'update', 'destroy']);

                Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
                Route::resource('classrooms', ClassroomController::class)->only(['store', 'update', 'destroy']);

                Route::get('/curriculum', [CurriculumController::class, 'edit'])->name('curriculum.edit');
                Route::put('/curriculum', [CurriculumController::class, 'update'])->name('curriculum.update');
                Route::post('/curriculum/copy', [CurriculumController::class, 'copy'])->name('curriculum.copy');

                Route::get('/grading', [GradingScaleController::class, 'index'])->name('grading.index');
                Route::put('/grading/{stage}', [GradingScaleController::class, 'update'])->name('grading.update');
            });

            Route::middleware('can:'.Permission::ManagePeople->value)->prefix('people')->name('people.')->group(function () {
                Route::resource('students', StudentController::class)->except(['destroy']);
                Route::post('/students/{student}/guardians', [StudentGuardianController::class, 'store'])->name('students.guardians.store');
                Route::put('/students/{student}/guardians/{guardian}', [StudentGuardianController::class, 'update'])->name('students.guardians.update');
                Route::delete('/students/{student}/guardians/{guardian}', [StudentGuardianController::class, 'destroy'])->name('students.guardians.destroy');
                Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store'])->name('students.documents.store');
                Route::delete('/students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])->name('students.documents.destroy');

                Route::get('/guardians', [GuardianController::class, 'index'])->name('guardians.index');
                Route::get('/guardians/lookup', [StudentGuardianController::class, 'lookup'])->name('guardians.lookup');

                Route::resource('teachers', TeacherController::class)->except(['show', 'destroy']);
                Route::post('/teachers/{teacher}/invite', [TeacherController::class, 'invite'])->name('teachers.invite');

                Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment.index');
                Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollment.store');
                Route::put('/enrollments', [EnrollmentController::class, 'move'])->name('enrollment.move');
                Route::delete('/enrollments', [EnrollmentController::class, 'destroy'])->name('enrollment.destroy');

                Route::get('/import', [StudentImportController::class, 'create'])->name('import.create');
                Route::get('/import/template', [StudentImportController::class, 'template'])->name('import.template');
                Route::post('/import', [StudentImportController::class, 'store'])->name('import.store');
                Route::get('/import/{import}', [StudentImportController::class, 'show'])->name('import.show');
                Route::post('/import/{import}/confirm', [StudentImportController::class, 'confirm'])->name('import.confirm');
            });

            Route::middleware('can:'.Permission::ManageMembers->value)->group(function () {
                Route::get('/members', [MemberController::class, 'index'])->name('members.index');
                Route::post('/members/invitations', [MemberController::class, 'invite'])->name('members.invite');
                Route::post('/members/{member}/password', [MemberController::class, 'resetPassword'])->name('members.password');
            });
        });
    });
});
