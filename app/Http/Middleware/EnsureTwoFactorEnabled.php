<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Director, academic office and accountant must turn on 2FA before using the school. */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->needsTwoFactor() && ! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('security.show')->with('error', __('security.two_factor_required'));
        }

        return $next($request);
    }
}
