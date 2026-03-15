<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('super-admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('multi-company')) {
        $companies = $user->getAllCompanies();
        if ($companies->count() > 1 && ! session('selected_company_id')) {
            return redirect()->route('companies.select');
        }

        if ($companies->count() === 1) {
            $company = $companies->first();
            session(['selected_company_id' => $company->id]);

            return redirect()->route('companies.dashboard', $company);
        }

        return redirect()->route('companies.index');
    }

    if ($user->hasAnyRole('admin', 'contador', 'recursos-humanos')) {
        $companies = $user->getAllCompanies();
        if ($companies->count() > 1 && ! session('selected_company_id')) {
            return redirect()->route('companies.select');
        }

        $company = $user->company ?? $user->employee?->company;
        if ($company) {
            return redirect()->route('companies.dashboard', $company);
        }
    }

    if ($user->hasRole('employee')) {
        return redirect()->route('employee.profile.show');
    }

    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
