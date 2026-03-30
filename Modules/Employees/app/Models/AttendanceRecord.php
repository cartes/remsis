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
    ];

    protected $casts = [
        'date'             => 'date',
        'overtime_minutes' => 'integer',
        'delay_minutes'    => 'integer',
    ];

    /** Etiquetas legibles para cada estado */
    public static array $statusLabels = [
        'asistio'   => 'Asistió',
        'ausente'   => 'Ausente',
        'atraso'    => 'Atraso',
        'licencia'  => 'Licencia',
        'vacaciones' => 'Vacaciones',
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
