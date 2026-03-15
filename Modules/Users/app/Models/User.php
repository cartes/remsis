<?php

namespace Modules\Users\Models;

use Illuminate\Support\Facades\Storage;
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

    protected $appends = [
        'profile_photo_url',
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

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    public function getAllCompanies()
    {
        $companies = $this->companies()->get();
        
        $primaryCompany = $this->company;
        if ($primaryCompany && ! $companies->contains('id', $primaryCompany->id)) {
            $companies->push($primaryCompany);
        }

        return $companies;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        return Storage::url($this->profile_photo);
    }
}
