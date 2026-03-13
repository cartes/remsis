<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;
use Modules\Payroll\Models\Freelancer;
use Modules\AdminPanel\Models\Bank;

class FreelancerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Company $company)
    {
        $freelancers = Freelancer::where('company_id', $company->id)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
            
        $banks = Bank::orderBy('name')->get();

        return view('payroll::freelancers.index', compact('company', 'freelancers', 'banks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => 'required|string|unique:freelancers,rut',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'profession' => 'nullable|string|max:255',
            'default_retention_rate' => 'numeric|min:0|max:100',
        ]);

        $validated['company_id'] = $company->id;
        $validated['default_retention_rate'] = $validated['default_retention_rate'] ?? 13.75;
        $validated['is_active'] = true;

        $freelancer = Freelancer::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Colaborador creado exitosamente',
            'freelancer' => $freelancer
        ]);
    }

    /**
     * Search for freelancers.
     */
    public function search(Request $request, Company $company)
    {
        $query = $request->get('query');
        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $freelancers = Freelancer::where('company_id', $company->id)
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('rut', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'email', 'rut')
            ->take(10)
            ->get();

        return response()->json($freelancers);
    }

    /**
     * Get freelancer data for modal.
     */
    public function show(Company $company, Freelancer $freelancer)
    {
        if ($freelancer->company_id !== $company->id) {
            abort(403);
        }

        $freelancer->load('receipts');

        return response()->json([
            'status' => 'success',
            'freelancer' => $freelancer
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company, Freelancer $freelancer)
    {
        if ($freelancer->company_id !== $company->id) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => 'required|string|unique:freelancers,rut,' . $freelancer->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_type' => 'nullable|string|max:50',
            'default_gross_fee' => 'nullable|numeric|min:0',
            'default_retention_rate' => 'numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $freelancer->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Ficha actualizada exitosamente',
            'freelancer' => $freelancer
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, Freelancer $freelancer)
    {
        if ($freelancer->company_id !== $company->id) {
            abort(403);
        }

        $freelancer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Colaborador desvinculado exitosamente'
        ]);
    }
}
