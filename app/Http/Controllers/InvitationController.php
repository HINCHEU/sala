<?php

namespace App\Http\Controllers;

use App\Domain\School\Actions\AcceptInvitation;
use App\Domain\School\Models\SchoolInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitation links: a new person sets a name and password; an existing account signs in first,
 * then accepts. Only the account the invitation was addressed to may accept it.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = SchoolInvitation::findByToken($token);
        $user = $request->user();

        $state = match (true) {
            $invitation === null => 'invalid',
            $user !== null => $invitation->matches($user) ? 'accept' : 'mismatch',
            $this->existingUser($invitation) !== null => 'login',
            default => 'register',
        };

        if ($state === 'login') {
            session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('Invitations/Accept', [
            'state' => $state,
            'token' => $token,
            'invitation' => $invitation ? [
                'school' => $invitation->school()->value('name_km'),
                'role' => $invitation->role->value,
                'email' => $invitation->email,
                'phone' => $invitation->phone,
            ] : null,
        ]);
    }

    public function accept(Request $request, string $token, AcceptInvitation $accept): RedirectResponse
    {
        $invitation = SchoolInvitation::findByToken($token) ?? abort(404);
        $user = $request->user();

        if ($user) {
            abort_unless($invitation->matches($user), 403);
        } else {
            abort_if($this->existingUser($invitation) !== null, 409);

            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = DB::transaction(fn () => User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'phone' => $invitation->phone,
                'password' => $data['password'],
            ]));
            Auth::login($user);
            $request->session()->regenerate();
        }

        $accept->handle($invitation, $user);
        $request->session()->put('school_id', $invitation->school_id);

        return redirect()->route('dashboard')->with('success', __('members.joined'));
    }

    private function existingUser(SchoolInvitation $invitation): ?User
    {
        return User::query()
            ->where(fn ($q) => $q
                ->when($invitation->email, fn ($q) => $q->orWhere('email', $invitation->email))
                ->when($invitation->phone, fn ($q) => $q->orWhere('phone', $invitation->phone)))
            ->first();
    }
}
