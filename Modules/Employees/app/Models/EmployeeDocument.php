<?php

namespace Modules\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'title',
        'document_type',
        'file_path',
        'signature_status',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    // ── Labels ────────────────────────────────────────────────────────────

    public const TYPE_LABELS = [
        'contrato'    => 'Contrato',
        'anexo'       => 'Anexo',
        'comprobante' => 'Comprobante',
        'legal'       => 'Documento Legal',
        'otro'        => 'Otro',
    ];

    public const SIGNATURE_LABELS = [
        'sin_firma'               => 'Sin firma',
        'pendiente_colaborador'   => 'Pendiente Colaborador',
        'pendiente_empresa'       => 'Pendiente Empresa',
        'firmado_completamente'   => 'Firmado',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->document_type] ?? $this->document_type;
    }

    public function getSignatureLabelAttribute(): string
    {
        return self::SIGNATURE_LABELS[$this->signature_status] ?? $this->signature_status;
    }

    public function getDownloadUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
