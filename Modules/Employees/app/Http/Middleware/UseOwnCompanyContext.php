<?php

declare(strict_types=1);

namespace Modules\Employees\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Employees\Models\Employee;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal personal: un colaborador sin funciones administrativas no tiene empresa "administrada",
 * por lo que el contexto de tenant queda vacío y los modelos filtrados por tenant no devuelven nada
 * (política de días administrativos, saldos, solicitudes). Dentro de las rutas del portal se fija
 * como contexto la empresa de su propio vínculo laboral; cada controlador sigue limitando los datos
 * al colaborador autenticado.
 */
class UseOwnCompanyContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $this->tenantContext->hasTenant() && ! $this->tenantContext->isBypassed()) {
            $companyId = Employee::withoutGlobalScopes()->where('user_id', $user->id)->value('company_id');

            if ($companyId !== null) {
                $this->tenantContext->setCompanyId((int) $companyId);
            }
        }

        return $next($request);
    }
}
