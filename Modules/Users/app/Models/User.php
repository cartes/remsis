<?php

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
// use Modules\Users\Database\Factories\UserFactory;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    protected $guard_name = 'web';

    protected $with = ['employee.company','roles'];

    public function company()
    {
        return $this->hasOneThrough(Company::class, Employee::class, 'user_id', 'id', 'id', 'company_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }
}
