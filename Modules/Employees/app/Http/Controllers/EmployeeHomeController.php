<?php

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('employees::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show()
    {
        $user = auth()->user();

        $employee = $user->employee()->with('company')->first();

        return view('employees::profile.show', compact('user', 'employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('employees::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
