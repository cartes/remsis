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

        // Prioridad 1: Parámetro de ruta {company} (útil para URLs anidadas)
        $companyParam = $request->route('company');
        if ($companyParam) {
            if ($companyParam instanceof \Modules\Companies\Models\Company) {
                $tenantContext->setCompanyId($companyParam->id);
            } elseif (is_string($companyParam) || is_numeric($companyParam)) {
                // Si aún no se ha resuelto por Route Model Binding (por el orden del middleware)
                $company = \Modules\Companies\Models\Company::where('slug', $companyParam)
                    ->orWhere('id', (int) $companyParam)
                    ->first();
                if ($company) {
                    $tenantContext->setCompanyId($company->id);
                }
            }
        }

        // Prioridad 2: Sesión o Empresa del usuario autenticado (si no se detectó por URL)
        if (! $tenantContext->hasTenant()) {
            $tenantContext->initializeForUser($request->user());
        }

        try {
            return $next($request);
        } finally {
            $tenantContext->clear();
        }
    }
}
