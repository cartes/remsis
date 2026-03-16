<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Employees\Models\Employee;

// use Modules\AdminPanel\Database\Factories\IsapreFactory;

class Isapre extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['nombre', 'code'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
