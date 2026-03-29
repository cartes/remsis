<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutual extends Model
{
    use HasFactory;

    protected $table = 'mutuales';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['nombre'];
}
