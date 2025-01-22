<?php

namespace TenantAware\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantSessions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantSwitcher = app()['tenantSwitcher'] ?? null;

        // For the the CLI, etc:
        if (! $tenantSwitcher) {
            return $next($request);
        }

        if (! $request->session()->has('tenant_id')) {
            $request->session()->put('tenant_id', $tenantSwitcher['id']);

            return $next($request);
        }

        if ($request->session()->get('tenant_id') !== $tenantSwitcher['id']) {
            abort(401);
        }

        return $next($request);
    }
}
