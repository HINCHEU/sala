<?php

namespace App\Support\Tenancy;

use App\Domain\School\Models\School;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets CurrentSchool (and the spatie team) for a signed-in user: from the subdomain when
 * TENANT_DOMAIN is set, otherwise from the session switcher, otherwise the user's first school.
 * A school the user does not belong to is a 404, never a 403, so school codes cannot be probed.
 */
class ResolveSchool
{
    public function __construct(private CurrentSchool $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $school = $this->fromSubdomain($request);

            if ($school) {
                abort_unless($user->canAccessSchool($school), 404);
            } else {
                $school = $this->fromSession($request, $user);
            }

            $this->current->set($school);
            $request->session()->put('school_id', $school?->id);
        }

        return $next($request);
    }

    private function fromSubdomain(Request $request): ?School
    {
        $domain = config('app.tenant_domain');
        $host = $request->getHost();

        if (! $domain || ! str_ends_with($host, '.'.$domain)) {
            return null;
        }

        return School::where('code', substr($host, 0, -strlen('.'.$domain)))->first() ?? abort(404);
    }

    private function fromSession(Request $request, User $user): ?School
    {
        $id = $request->session()->get('school_id');
        $school = $id ? School::find($id) : null;

        // A membership can end mid-session: fall back to a school the user still belongs to.
        if ($school && $user->canAccessSchool($school)) {
            return $school;
        }

        return $user->accessibleSchools()->first();
    }
}
