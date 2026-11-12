<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

/**
 * Representa un Tenant (organización, holding o estudio contable) dentro del SaaS.
 * Un Tenant puede administrar múltiples razones sociales (Company / Multi-RUT).
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_TRIAL = 'trial';

    protected $fillable = [
        'name',
        'rut',
        'slug',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            if (empty($tenant->slug) && ! empty($tenant->name)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });

        static::updating(function (self $tenant): void {
            if ($tenant->isDirty('name') && ! $tenant->isDirty('slug')) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /**
     * Razones sociales (RUTs) pertenecientes a este tenant.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'tenant_id');
    }

    /**
     * Usuarios asociados directamente a la cuenta del tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

