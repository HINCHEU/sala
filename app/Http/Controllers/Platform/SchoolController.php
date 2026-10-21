<?php

namespace App\Http\Controllers\Platform;

use App\Domain\School\Actions\CreateSchool;
use App\Domain\School\Actions\InviteMember;
use App\Domain\School\Enums\Role;
use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Domain\School\Models\School;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvitationRequest;
use App\Http\Requests\Platform\StoreSchoolRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Schools/Index', [
            'schools' => School::withCount('activeUsers')->orderBy('name_km')->get()
                ->map(fn (School $school) => [
                    'id' => $school->id,
                    'code' => $school->code,
                    'name_km' => $school->name_km,
                    'name_en' => $school->name_en,
                    'province' => $school->province,
                    'members' => $school->active_users_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Schools/Create', [
            'types' => array_column(SchoolType::cases(), 'value'),
            'stages' => array_column(SchoolStage::cases(), 'value'),
        ]);
    }

    public function store(StoreSchoolRequest $request, CreateSchool $createSchool, InviteMember $invite): RedirectResponse
    {
        $school = $createSchool->handle($request->schoolData());
        $link = $invite->handle($school, Role::Director, $request->email(), $request->phone(), $request->user());

        return redirect()->route('platform.schools.index')
            ->with('success', __('platform.school_created'))
            ->with('invite_link', $link);
    }

    /** A fresh director link, e.g. when the first one expired. */
    public function invite(InvitationRequest $request, School $school, InviteMember $invite): RedirectResponse
    {
        $link = $invite->handle($school, Role::Director, $request->email(), $request->phone(), $request->user());

        return back()->with('invite_link', $link);
    }
}
