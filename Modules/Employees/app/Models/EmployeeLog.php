<?php

namespace Modules\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Users\Models\User;

class EmployeeLog extends Model
{
    protected $table = 'employee_logs';

    // ── Tipos de evento ───────────────────────────────────────
    const TYPE_CREACION     = 'creacion';
    const TYPE_CONTRATO     = 'contrato';
    const TYPE_REMUNERACION = 'remuneracion';
    const TYPE_AUSENTISMO   = 'ausentismo';
    const TYPE_AUDITORIA    = 'auditoria';
    const TYPE_SISTEMA      = 'sistema';

    protected $fillable = [
        'employee_id',
        'user_id',
        'type',
        'description',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ── Etiquetas legibles para campos del empleado ───────────
    public static array $fieldLabels = [
        'first_name'           => 'Nombres',
        'last_name'            => 'Apellidos',
        'rut'                  => 'RUT',
        'email'                => 'Correo electrónico',
        'phone'                => 'Teléfono',
        'address'              => 'Dirección',
        'position'             => 'Cargo',
        'birth_date'           => 'Fecha de nacimiento',
        'nationality'          => 'Nacionalidad',
        'marital_status'       => 'Estado civil',
        'num_dependents'       => 'N° de cargas',
        'hire_date'            => 'Fecha de ingreso',
        'salary'               => 'Sueldo base',
        'salary_type'          => 'Tipo de remuneración',
        'contract_type'        => 'Tipo de contrato',
        'afp_id'               => 'AFP',
        'isapre_id'            => 'Isapre',
        'health_system'        => 'Sistema de salud',
        'health_contribution'  => 'Valor plan de salud',
        'apv_amount'           => 'Monto APV',
        'cost_center_id'       => 'Área / Departamento',
        'is_in_payroll'        => 'En nómina de pago',
        'payment_method'       => 'Método de pago',
        'bank_id'              => 'Banco',
        'bank_account_number'  => 'N° de cuenta',
        'bank_account_type'    => 'Tipo de cuenta',
        'gender'               => 'Género',
        'status'               => 'Estado',
        'work_schedule'        => 'Jornada laboral',
        'work_schedule_type'   => 'Tipo de jornada',
        'part_time_hours'      => 'Horas part-time',
        'ccaf_id'              => 'CCAF',
        'emergency_contact_name'  => 'Contacto de emergencia',
        'emergency_contact_phone' => 'Teléfono emergencia',
    ];

    // ── Etiquetas legibles para el tipo de evento ─────────────
    public static array $typeLabels = [
        self::TYPE_CREACION     => 'Creación',
        self::TYPE_CONTRATO     => 'Contrato',
        self::TYPE_REMUNERACION => 'Remuneración',
        self::TYPE_AUSENTISMO   => 'Ausentismo',
        self::TYPE_AUDITORIA    => 'Auditoría',
        self::TYPE_SISTEMA      => 'Sistema',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
