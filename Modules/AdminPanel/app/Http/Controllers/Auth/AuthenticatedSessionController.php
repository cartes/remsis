<?php

namespace Modules\AdminPanel\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('adminpanel::auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            $redirectUrl = route('admin.dashboard');
        } elseif ($user->hasRole('multi-company')) {
            $companies = $user->getAllCompanies();
            if ($companies->count() > 1) {
                $redirectUrl = route('companies.select');
            } elseif ($companies->count() === 1) {
                $company = $companies->first();
                session(['selected_company_id' => $company->id]);
                $redirectUrl = route('companies.dashboard', $company);
            } else {
                $redirectUrl = route('companies.index');
            }
        } elseif ($user->hasAnyRole('admin', 'contador', 'recursos-humanos')) {
            $companies = $user->getAllCompanies();
            if ($companies->count() > 1) {
                $redirectUrl = route('companies.select');
            } else {
                $company = $user->company ?? $user->employee?->company;
                if ($company) {
                    session(['selected_company_id' => $company->id]);
                    $redirectUrl = route('companies.dashboard', $company);
                } else {
                    $redirectUrl = route('admin.dashboard'); // Fallback or handle error
                }
            }
        } elseif ($user->hasRole('employee')) {
            $redirectUrl = route('employee.profile.show');
        } else {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tienes rol asignado para ingresar.',
                    'errors' => ['email' => ['No tienes rol asignado para ingresar.']],
                ], 403);
            }

            return redirect()->route('admin.login')->withErrors(['email' => 'No tienes rol asignado para ingresar.']);
        }

        $redirectUrl = redirect()->intended($redirectUrl)->getTargetUrl();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirectUrl]);
        }

        return redirect()->to($redirectUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
