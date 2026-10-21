<?php

namespace App\Http\Controllers;

use App\Domain\School\Models\School;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AccountController extends Controller
{
    public function dashboard(CurrentSchool $current): Response
    {
        return Inertia::render('Dashboard', ['hasSchool' => $current->get() !== null]);
    }

    /** Works for guests too, so the login page can switch language. */
    public function locale(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', Rule::in(SetLocale::LOCALES)]])['locale'];

        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back();
    }

    public function switchSchool(Request $request): SymfonyResponse
    {
        /** @var User $user */
        $user = $request->user();
        $school = School::find($request->integer('school_id'));

        abort_unless($school && $user->canAccessSchool($school), 404);
        $request->session()->put('school_id', $school->id);

        // Full page load: the school's brand colour is set on <html>.
        return Inertia::location(route('dashboard'));
    }

    /** Two-factor setup and password change. */
    public function security(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $pending = $user->two_factor_secret && ! $user->hasConfirmedTwoFactor();

        return Inertia::render('Security', [
            'twoFactor' => [
                'required' => $user->needsTwoFactor(),
                'enabled' => $user->two_factor_secret !== null,
                'confirmed' => $user->hasConfirmedTwoFactor(),
                'qrSvg' => $pending ? $user->twoFactorQrCodeSvg() : null,
                'setupKey' => $pending ? decrypt($user->two_factor_secret) : null,
                'recoveryCodes' => $user->hasConfirmedTwoFactor() ? $user->recoveryCodes() : [],
            ],
        ]);
    }
}
