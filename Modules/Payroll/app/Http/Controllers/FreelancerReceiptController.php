<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;
use Modules\Payroll\Models\Freelancer;
use Modules\Payroll\Models\FreelancerReceipt;

class FreelancerReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('payroll::index');
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
    public function store(Request $request, Company $company, Freelancer $freelancer)
    {
        if ($freelancer->company_id !== $company->id) {
            abort(403);
        }

        $validated = $request->validate([
            'receipt_number' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'gross_amount' => 'required|numeric|min:0',
            'retention_amount' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric|min:0',
            'issuer' => 'required|in:freelancer,company',
            'status' => 'required|in:pending,paid,annulled',
        ]);

        $validated['company_id'] = $company->id;
        $validated['freelancer_id'] = $freelancer->id;

        $receipt = FreelancerReceipt::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Boleta registrada exitosamente',
            'receipt' => $receipt,
        ]);
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
    public function update(Request $request, Company $company, Freelancer $freelancer, FreelancerReceipt $receipt)
    {
        if ($freelancer->company_id !== $company->id || $receipt->freelancer_id !== $freelancer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'receipt_number' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'gross_amount' => 'required|numeric|min:0',
            'retention_amount' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric|min:0',
            'issuer' => 'required|in:freelancer,company',
            'status' => 'required|in:pending,paid,annulled',
        ]);

        $receipt->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Boleta actualizada exitosamente',
            'receipt' => $receipt,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, Freelancer $freelancer, FreelancerReceipt $receipt)
    {
        if ($freelancer->company_id !== $company->id || $receipt->freelancer_id !== $freelancer->id) {
            abort(403);
        }

        $receipt->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Boleta eliminada exitosamente',
        ]);
    }
}
