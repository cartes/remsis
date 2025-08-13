<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Companies\Models\Company;
// use Modules\AdminPanel\Database\Factories\IsapreFactory;

class Isapre extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'code'];

  
}
