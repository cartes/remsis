<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
