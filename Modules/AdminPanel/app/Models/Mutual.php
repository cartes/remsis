<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Companies\Models\Company;

class Mutual extends Model
{
    use HasFactory;

    protected $table = 'mutuales';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['nombre'];

    // public function companies()
    // {
    //     return $this->hasMany(Company::class);
    // }
}
