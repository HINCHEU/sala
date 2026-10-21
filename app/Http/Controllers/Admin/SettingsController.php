<?php

namespace App\Http\Controllers\Admin;

use App\Domain\School\Actions\UpdateSchoolSettings;
use App\Domain\School\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private CurrentSchool $current) {}

    public function edit(): Response
    {
        $school = $this->current->get()->load('logo');

        return Inertia::render('School/Settings', [
            'school' => [
                'name_km' => $school->name_km,
                'name_en' => $school->name_en,
                'report_header' => $school->report_header,
                'brand_color' => $school->brand_color,
                'logo_url' => $school->logo?->url(),
            ],
            'settings' => $school->settings->toArray(),
            'roles' => Role::values(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSchoolSettings $update): RedirectResponse
    {
        $update->handle($this->current->get(), $request->profile(), $request->settings(), $request->file('logo'));

        return back()->with('success', __('settings.saved'));
    }
}
