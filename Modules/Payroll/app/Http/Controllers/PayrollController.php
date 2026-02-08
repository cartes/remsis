<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;
class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $company = null)
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            if ($company) {
                // Si tiene definida una empresa en la ruta, mostrar nóminas de esa empresa
                $companyModel = Company::findOrFail($company);
                $payrolls = \Modules\Payroll\Models\Payroll::with(['employee', 'company', 'period'])
                    ->where('company_id', $company)
                    ->paginate(15);
                return view('payroll::index', compact('payrolls', 'companyModel'));
            } else {
                $companies = Company::withCount('payrolls')->get();
                return view('payroll::index_companies', compact('companies'));
            }
        } else {
            // Para otros usuarios mostrar nóminas filtradas según permisos/empresa
            $payrolls = \Modules\Payroll\Models\Payroll::with(['employee', 'company', 'period'])
                ->where('company_id', $user->company_id)
                ->paginate(15);
            $companyModel = Company::find($user->company_id);
            return view('payroll::index', compact('payrolls', 'companyModel'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payroll::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('payroll::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('payroll::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
    }
}
