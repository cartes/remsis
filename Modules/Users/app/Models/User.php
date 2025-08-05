<?php

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Modules\Companies\Models\Company;
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
        return $this->belongsTo(Company::class);
    }
}
