<?php

namespace Modules\Users\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\Employee;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'email',
        'password',
        'status',
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

    protected $with = ['employee.company', 'roles'];

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (
                ! empty($user->company_id)
                && ($user->isDirty('company_id') || empty($user->tenant_id))
                && Schema::hasTable('companies')
                && Schema::hasColumn('companies', 'tenant_id')
            ) {
                $companyTenantId = Company::query()
                    ->withoutGlobalScopes()
                    ->whereKey($user->company_id)
                    ->value('tenant_id');

                if ($companyTenantId !== null) {
                    $user->tenant_id = (int) $companyTenantId;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function company()
    {
        return $this->hasOneThrough(Company::class, Employee::class, 'user_id', 'id', 'id', 'company_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * Membresías contextuales del usuario sobre empresas/tenants (incluyendo outsourcing).
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
    }

    /**
     * Retorna todas las empresas (tenants 1:1) a las que el usuario tiene acceso autorizado
     * por membresía explícita (company_user), vínculo laboral (employee) o asignación directa.
     *
     * @return Collection<int, Company>
     */
    public function getAllCompanies(): Collection
    {
        $companies = $this->companies()->get();

        $primaryCompany = $this->company ?? $this->employee?->company;
        if ($primaryCompany && ! $companies->contains('id', $primaryCompany->id)) {
            $companies->push($primaryCompany);
        }

        if (! empty($this->company_id) && ! $companies->contains('id', (int) $this->company_id)) {
            $directCompany = Company::query()->withoutGlobalScopes()->find((int) $this->company_id);
            if ($directCompany) {
                $companies->push($directCompany);
            }
        }

        if (! empty($this->tenant_id)) {
            $tenantCompany = Company::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', (int) $this->tenant_id)
                ->first();

            if ($tenantCompany && ! $companies->contains('id', $tenantCompany->id)) {
                $companies->push($tenantCompany);
            }
        }

        return $companies->unique('id')->values();
    }

    /**
     * Determina si el usuario tiene acceso administrativo u operativo a una empresa (Company).
     */
    public function canAccessCompany(Company|int $company): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->withoutGlobalScopes()->find($company);

        if (! $companyModel) {
            return false;
        }

        if ($this->company_id !== null && (int) $this->company_id === (int) $companyModel->id) {
            return true;
        }

        if (
            $this->tenant_id !== null
            && $companyModel->tenant_id !== null
            && (int) $this->tenant_id === (int) $companyModel->tenant_id
        ) {
            return true;
        }

        if ($this->employee && (int) $this->employee->company_id === (int) $companyModel->id) {
            return true;
        }

        return $this->companies()->where('companies.id', $companyModel->id)->exists();
    }

    /**
     * Determina si el usuario tiene acceso al Tenant (1:1 con Company).
     */
    public function canAccessTenant(Tenant|int $tenant): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $tenantId = $tenant instanceof Tenant ? (int) $tenant->id : (int) $tenant;

        if ($this->tenant_id !== null && (int) $this->tenant_id === $tenantId) {
            return true;
        }

        $company = Company::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->first();

        return $company ? $this->canAccessCompany($company) : false;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        return Storage::url($this->profile_photo);
    }
}
