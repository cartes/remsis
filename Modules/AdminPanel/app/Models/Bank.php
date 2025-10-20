<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\AdminPanel\Database\Factories\BankFactory;

class Bank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'code', 'status'];

    public function employees()
    {
        return $this->hasMany(\Modules\Employees\Models\Employee::class);
    }

}
