<?php

namespace Modules\Employees\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'date',
        'scheduled_shift',
        'clock_in',
        'clock_out',
        'overtime_minutes',
        'delay_minutes',
        'status',
        'is_justified',
        'justification_type',
        'justification_reason',
    ];

    protected $casts = [
        'date'             => 'date',
        'overtime_minutes' => 'integer',
        'delay_minutes'    => 'integer',
        'is_justified'     => 'boolean',
    ];

    /** Etiquetas legibles para cada estado */
    public static array $statusLabels = [
        'asistio'   => 'Asistió',
        'ausente'   => 'Ausente',
        'atraso'    => 'Atraso',
        'licencia'  => 'Licencia',
        'vacaciones' => 'Vacaciones',
    ];

    /** Tipos de justificación compatibles con la legislación laboral chilena */
    public static array $justificationTypes = [
        'inasistencia_injustificada' => 'Inasistencia Injustificada (Descuenta)',
        'licencia_medica'            => 'Licencia Médica (Descuenta)',
        'permiso_con_goce'           => 'Permiso con Goce de Sueldo (No descuenta)',
        'permiso_sin_goce'           => 'Permiso sin Goce de Sueldo (Descuenta)',
        'accidente_trabajo'          => 'Accidente del Trabajo (Descuenta)',
        'suspension_disciplinaria'   => 'Sanción/Suspensión Disciplinaria (Descuenta)',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Devuelve las horas extras formateadas como "Xh Ym". */
    public function getFormattedOvertimeAttribute(): string
    {
        if ($this->overtime_minutes <= 0) {
            return '—';
        }
        $h = intdiv($this->overtime_minutes, 60);
        $m = $this->overtime_minutes % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }
}
