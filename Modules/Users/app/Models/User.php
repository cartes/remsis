<?php

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
// use Modules\Users\Database\Factories\UserFactory;

use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasFactory, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
    protected $guard_name = 'web';

    public function company()
    {
        return $this->hasOneThrough(Company::class, Employee::class, 'user_id', 'id', 'id', 'company_id');
    }

    public function employees()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }
}
