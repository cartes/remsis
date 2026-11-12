<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $user = $request->user();

        if ($user && $user->hasRole('super-admin')) {
            $tenantContext->bypass();
        }

        // Prioridad 1: Parámetro de ruta {company} validado contra las membresías del usuario
        $companyParam = $request->route('company');
        if ($companyParam) {
            $company = null;

            if ($companyParam instanceof Company) {
                $company = $companyParam;
            } elseif (is_string($companyParam) || is_numeric($companyParam)) {
                $company = Company::query()
                    ->withoutGlobalScopes()
                    ->where('slug', (string) $companyParam)
                    ->when(is_numeric($companyParam), fn ($q) => $q->orWhere('id', (int) $companyParam))
                    ->first();
            }

            if ($company) {
                $canUseRouteCompany = ! $user
                    || $user->canAccessCompany($company)
                    || $user->getAllCompanies()->isEmpty();

                if ($canUseRouteCompany) {
                    $tenantContext->setCompanyId($company->id);
                }
            }
        }

        // Prioridad 2: Sesión o Empresa autorizada del usuario autenticado
        if (! $tenantContext->hasTenant() && ! $tenantContext->isBypassed()) {
            $tenantContext->initializeForUser($user);
        }

        try {
            return $next($request);
        } finally {
            $tenantContext->clear();
        }
    }
}
