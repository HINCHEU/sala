<?php

namespace App\Http\Controllers\Admin;

use App\Domain\School\Actions\InviteMember;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\SchoolInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteMemberRequest;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(private CurrentSchool $current) {}

    public function index(): Response
    {
        $school = $this->current->get();

        return Inertia::render('School/Members', [
            'members' => $school->users()->with('roles')->orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->pivot->status->value,
                'roles' => $user->roles->pluck('name'),
            ]),
            'invitations' => SchoolInvitation::pending()->latest()->get(['id', 'email', 'phone', 'role', 'expires_at']),
            'roles' => Role::values(),
        ]);
    }

    public function invite(InviteMemberRequest $request, InviteMember $invite): RedirectResponse
    {
        $link = $invite->handle($this->current->get(), $request->role(), $request->email(), $request->phone(), $request->user());

        return back()->with('invite_link', $link);
    }

    /** "Reset by admin": for staff and parents without email. The temporary password is shown once. */
    public function resetPassword(Request $request, User $member): RedirectResponse
    {
        abort_unless($this->current->get()->users()->whereKey($member->id)->exists(), 404);
        abort_if($member->is($request->user()) || $member->is_platform_admin, 403);

        $password = Str::password(10, symbols: false);
        $member->forceFill(['password' => $password])->save();

        activity()->performedOn($member)->causedBy($request->user())->log('member.password_reset');

        return back()->with('temp_password', ['name' => $member->name, 'password' => $password]);
    }
}
