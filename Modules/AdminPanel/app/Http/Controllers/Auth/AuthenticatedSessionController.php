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

        $redirectUrl = route('admin.dashboard');

        if ($user->hasRole('employee')) {
            $redirectUrl = route('employee.profile.show');
        } elseif ($user->hasAnyRole('super-admin', 'admin', 'contador')) {
            $redirectUrl = redirect()->intended(route('admin.dashboard'))->getTargetUrl();
        } else {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tienes rol asignado para ingresar.',
                    'errors' => ['email' => ['No tienes rol asignado para ingresar.']]
                ], 403);
            }
            
            return redirect()->route('admin.login')->withErrors(['email' => 'No tienes rol asignado para ingresar.']);
        }

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
