<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $tenantContext->initializeForUser($request->user());

        try {
            return $next($request);
        } finally {
            $tenantContext->clear();
        }
    }
}
