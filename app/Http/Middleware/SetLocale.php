<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The user's choice wins, then the guest's session choice, then the school's language, then the app default. */
class SetLocale
{
    public const LOCALES = ['km', 'en'];

    public function __construct(private CurrentSchool $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $locale = ($user instanceof User ? $user->locale : null)
            ?? $request->session()->get('locale')
            ?? $this->current->get()?->settings->locale;

        if (in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
