<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;

class CompanySelectorController extends Controller
{
    public function index(Request $request)
    {
        $companies = $request->user()->getAllCompanies();

        return view('companies::select', compact('companies'));
    }

    public function select(Request $request, Company $company)
    {
        $user = $request->user();

        // Security check: Verify user actually has access to this company
        if (! $user->getAllCompanies()->contains('id', $company->id)) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        session(['selected_company_id' => $company->id]);

        return redirect()->route('companies.dashboard', $company);
    }
}
