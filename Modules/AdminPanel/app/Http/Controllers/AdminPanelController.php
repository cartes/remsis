<?php

namespace Modules\AdminPanel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Companies\Models\Company;

class AdminPanelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stats = [
            'active_companies' => ['label' => 'Empresas Activas', 'value' => Company::count(), 'change' => '+1', 'trend' => 'up'],
            'active_employees' => ['label' => 'Empleados Activos', 'value' => '1.240', 'change' => '+12', 'trend' => 'up'],
            'social_security' => ['label' => 'Previred Pendiente', 'value' => '$ 12.450.320', 'change' => '-5.1%', 'trend' => 'down'],
        ];

        $newUsers = [
            ['id' => 1, 'name' => 'María José Rivera', 'email' => 'm.rivera@empresa.cl', 'role' => 'Contador', 'date' => 'Hace 2 horas', 'avatar' => 'MR', 'status' => 'Activo'],
            ['id' => 2, 'name' => 'Cristóbal Salinas', 'email' => 'c.salinas@tecnologia.io', 'role' => 'Admin Empresa', 'date' => 'Hace 5 horas', 'avatar' => 'CS', 'status' => 'Pendiente'],
            ['id' => 3, 'name' => 'Valentina Soto', 'email' => 'v.soto@servicios.com', 'role' => 'Empleado', 'date' => 'Ayer', 'avatar' => 'VS', 'status' => 'Activo'],
        ];

        $taxDates = [
            ['title' => 'Declaración IVA (F29)', 'date' => '12 Feb', 'type' => 'Impuestos', 'priority' => 'Alta', 'days_left' => 3],
            ['title' => 'Pago Previred', 'date' => '13 Feb', 'type' => 'Previsión', 'priority' => 'Urgente', 'days_left' => 4],
            ['title' => 'Retención Honorarios', 'date' => '20 Feb', 'type' => 'Impuestos', 'priority' => 'Media', 'days_left' => 11],
            ['title' => 'Cierre de Nómina', 'date' => '25 Feb', 'type' => 'Payroll', 'priority' => 'Próximo', 'days_left' => 16],
        ];

        $messages = [
            ['sender' => 'Soporte Técnico', 'subject' => 'Actualización de Sistema v2.4', 'time' => '10:45 AM', 'is_read' => false, 'icon' => 'fa-robot'],
            ['sender' => 'Cristian Cartes', 'subject' => 'Consulta sobre liquidaciones', 'time' => 'Ayer', 'is_read' => true, 'icon' => 'fa-user'],
            ['sender' => 'SII Notificaciones', 'subject' => 'Nueva circular DTE', 'time' => '05 Feb', 'is_read' => true, 'icon' => 'fa-building-columns'],
        ];

        $payrollHistory = [
            ['month' => 'Oct', 'amount' => 38.5],
            ['month' => 'Nov', 'amount' => 41.2],
            ['month' => 'Dic', 'amount' => 45.2],
        ];

        return view('adminpanel::index', compact('stats', 'newUsers', 'taxDates', 'messages', 'payrollHistory'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('adminpanel::create');
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
        return view('adminpanel::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('adminpanel::edit');
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

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
