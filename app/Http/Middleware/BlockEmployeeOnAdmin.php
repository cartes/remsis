<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockEmployeeOnAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (
            $request->is('admin/*')
            && $user
            && $user->hasRole('employee')
            && ! $user->hasAnyRole(\Modules\Users\Models\User::ADMIN_ROLES)
            && ! $user->hasRole('super-admin')
        ) {
            return redirect()->route('employee.profile.show');
        }

        return $next($request);
    }
}
