<?php

namespace App\Http\Middleware;

use App\Domain\School\Enums\Permission;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $school = app(CurrentSchool::class)->get()?->loadMissing('logo');

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user?->only('id', 'name', 'email', 'phone', 'is_platform_admin'),
            ],
            'school' => fn () => $school ? [
                'id' => $school->id,
                'name' => $school->name(),
                'name_km' => $school->name_km,
                'name_en' => $school->name_en,
                'logo_url' => $school->logo?->url(),
                'brand_color' => $school->brand_color,
                'brand_color_soft' => $school->brand_color_soft,
            ] : null,
            'schools' => fn () => $user instanceof User
                ? $user->accessibleSchools()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name()])
                : [],
            'roles' => fn () => $user instanceof User ? array_map(fn ($r) => $r->value, $user->schoolRoles()) : [],
            'can' => fn () => [
                'platform' => (bool) $user?->is_platform_admin,
                'manageSettings' => $school && $user?->can(Permission::ManageSettings->value),
                'manageMembers' => $school && $user?->can(Permission::ManageMembers->value),
                'manageAcademic' => $school && $user?->can(Permission::ManageAcademic->value),
                'managePeople' => $school && $user?->can(Permission::ManagePeople->value),
                'manageExams' => $school && $user?->can(Permission::ManageExams->value),
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
                'inviteLink' => $request->session()->get('invite_link'),
                'tempPassword' => $request->session()->get('temp_password'),
                'scoreImportErrors' => $request->session()->get('score_import_errors'),
            ],
        ];
    }
}
