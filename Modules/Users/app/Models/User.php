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

    public const ADMIN_ROLES = [
        'admin',
        'contador',
        'recursos-humanos',
        'multi-company',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'admin' => [
            'company.manage',
            'users.manage',
            'employees.manage',
            'payroll.view',
            'payroll.calculate',
            'payroll.approve',
            'requests.manage',
            'documents.manage',
        ],
        'multi-company' => [
            'company.manage',
            'users.manage',
            'employees.manage',
            'payroll.view',
            'payroll.calculate',
            'payroll.approve',
            'requests.manage',
            'documents.manage',
        ],
        'contador' => [
            'company.manage',
            'users.manage',
            'employees.manage',
            'payroll.view',
            'payroll.calculate',
            'payroll.approve',
            'documents.manage',
        ],
        'recursos-humanos' => [
            'employees.manage',
            'requests.manage',
            'documents.manage',
            'payroll.view',
        ],
    ];

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
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot(['role', 'permissions', 'is_external', 'delegated_by'])
            ->withTimestamps();
    }

    /**
     * Determina si el usuario tiene acceso a su portal personal ("Mi Remsys").
     * Aplica a todo colaborador con vínculo laboral (Employee), tenga o no funciones administrativas,
     * pero excluye a contadores externos / outsourcing sin vínculo laboral.
     */
    public function hasPersonalPortalAccess(): bool
    {
        if ($this->relationLoaded('employee') ? $this->employee !== null : $this->employee()->exists()) {
            return true;
        }

        return $this->hasRole('employee');
    }

    /**
     * Verifica si el usuario tiene vínculo (laboral o de membresía) con la empresa indicada.
     */
    public function belongsToCompany(Company|int $company): bool
    {
        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->withoutGlobalScopes()->find($company);

        if (! $companyModel) {
            return false;
        }

        if ($this->companies()->where('companies.id', $companyModel->id)->exists()) {
            return true;
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

        return $this->employee !== null && (int) $this->employee->company_id === (int) $companyModel->id;
    }

    /**
     * Verifica si existe una membresía administrativa explícita en company_user para esta empresa.
     */
    public function hasAdministrativeMembership(Company|int $company): bool
    {
        $companyId = $company instanceof Company ? (int) $company->id : (int) $company;

        $membership = $this->companies()
            ->where('companies.id', $companyId)
            ->first();

        if (! $membership) {
            return false;
        }

        $pivotRole = $membership->pivot?->role;
        $pivotPermissions = $this->decodePivotPermissions($membership->pivot?->permissions ?? null);

        if ($pivotRole !== null) {
            return in_array($pivotRole, self::ADMIN_ROLES, true) || ! empty($pivotPermissions);
        }

        // Si fue vinculado en company_user sin especificar pivot role, cuenta como membresía administrativa
        // salvo que el usuario tenga exclusivamente el rol 'employee' sin permisos delegados.
        return ! ($this->hasRole('employee') && ! $this->hasAnyRole(self::ADMIN_ROLES) && empty($pivotPermissions));
    }

    /**
     * Retorna todas las empresas (tenants 1:1) a las que el usuario pertenece.
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
     * Separa explícitamente el acceso personal de colaborador ('employee') de la gestión empresarial.
     */
    public function canAccessCompany(Company|int $company): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->withoutGlobalScopes()->find($company);

        if (! $companyModel || ! $this->belongsToCompany($companyModel)) {
            return false;
        }

        // Si el usuario tiene únicamente el rol personal 'employee' y no posee rol ni membresía administrativa
        // en esta empresa, solo puede acceder a "Mi Remsys" (su portal personal), no a la gestión del tenant.
        if (
            $this->hasRole('employee')
            && ! $this->hasAnyRole(self::ADMIN_ROLES)
            && ! $this->hasAdministrativeMembership($companyModel)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene el rol administrativo efectivo del usuario dentro de una empresa específica.
     */
    public function roleInCompany(Company|int $company): ?string
    {
        if ($this->hasRole('super-admin')) {
            return 'super-admin';
        }

        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->withoutGlobalScopes()->find($company);

        if (! $companyModel || ! $this->canAccessCompany($companyModel)) {
            return null;
        }

        $membership = $this->companies()->where('companies.id', $companyModel->id)->first();
        if ($membership && ! empty($membership->pivot?->role)) {
            return (string) $membership->pivot->role;
        }

        foreach (['admin', 'contador', 'recursos-humanos', 'multi-company'] as $roleName) {
            if ($this->hasRole($roleName)) {
                return $roleName;
            }
        }

        return null;
    }

    /**
     * Obtiene la lista de permisos/funciones autorizadas del usuario dentro de una empresa específica.
     *
     * @return array<int, string>
     */
    public function permissionsInCompany(Company|int $company): array
    {
        if ($this->hasRole('super-admin')) {
            return self::DEFAULT_ROLE_PERMISSIONS['admin'];
        }

        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->withoutGlobalScopes()->find($company);

        if (! $companyModel || ! $this->canAccessCompany($companyModel)) {
            return [];
        }

        $membership = $this->companies()->where('companies.id', $companyModel->id)->first();
        if ($membership) {
            $delegated = $this->decodePivotPermissions($membership->pivot?->permissions ?? null);
            if (! empty($delegated)) {
                return $delegated;
            }
        }

        $role = $this->roleInCompany($companyModel);

        return $role !== null && isset(self::DEFAULT_ROLE_PERMISSIONS[$role])
            ? self::DEFAULT_ROLE_PERMISSIONS[$role]
            : self::DEFAULT_ROLE_PERMISSIONS['admin'];
    }

    /**
     * Verifica si el usuario posee una capacidad/permiso administrativo específico dentro de la empresa.
     */
    public function hasCompanyPermission(Company|int $company, string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        if (! $this->canAccessCompany($company)) {
            return false;
        }

        return in_array($permission, $this->permissionsInCompany($company), true);
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

        $company = Company::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->first();

        if ($company) {
            return $this->canAccessCompany($company);
        }

        return $this->tenant_id !== null
            && (int) $this->tenant_id === $tenantId
            && ! ($this->hasRole('employee') && ! $this->hasAnyRole(self::ADMIN_ROLES));
    }

    /**
     * @return array<int, string>
     */
    protected function decodePivotPermissions(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return [];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        return Storage::url($this->profile_photo);
    }
}
