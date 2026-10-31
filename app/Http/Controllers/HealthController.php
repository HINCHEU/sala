<?php

namespace App\Http\Controllers;

use App\Support\Monitoring\HealthChecks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** For an outside uptime poller. Needs the token, because the answer describes the system. /up stays public. */
class HealthController
{
    public function __invoke(Request $request, HealthChecks $health): JsonResponse
    {
        $token = (string) config('services.monitoring.health_token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->query('token')), 404);

        $checks = $health->run();
        $ok = array_filter($checks) === [];

        return response()->json(['ok' => $ok, 'checks' => array_map(fn (?string $reason) => $reason ?? 'ok', $checks)], $ok ? 200 : 503);
    }
}
