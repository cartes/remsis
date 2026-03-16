<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Employees\Models\Employee;

// use Modules\AdminPanel\Database\Factories\AfpFactory;

class Afp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['nombre', 'code', 'rate', 'commission'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
