<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentSchool
{
    public function __construct(private CurrentSchool $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->current->get()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
