<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Companies\Models\Company;
// use Modules\AdminPanel\Database\Factories\CcafFactory;

class Ccaf extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['nombre', 'code'];

    // protected static function newFactory(): CcafFactory
    // {
    //     // return CcafFactory::new();
    // }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
