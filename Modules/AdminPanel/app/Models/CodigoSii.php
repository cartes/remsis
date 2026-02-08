<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoSii extends Model
{
    use HasFactory;

    protected $table = 'codigos_sii';

    protected $fillable = [
        'codigo',
        'glosa',
        'utms_min',
        'categoria',
        'activo',
    ];

    protected $casts = [
        'utms_min' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
