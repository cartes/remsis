<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EconomicActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
    ];

    public $timestamps = false;
}
