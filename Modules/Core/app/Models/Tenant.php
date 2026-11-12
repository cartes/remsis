<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Rules\Rut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

/**
 * Representa un Tenant dentro del SaaS bajo la invariante ADR-001:
 * 1 RUT chileno = 1 Empresa (Company) = 1 Tenant.
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
            if (! empty($tenant->rut)) {
                $tenant->rut = Rut::normalize($tenant->rut);
            }

            if (empty($tenant->slug) && ! empty($tenant->name)) {
                $tenant->slug = static::generateUniqueSlug($tenant->name);
            }
        });

        static::updating(function (self $tenant): void {
            if ($tenant->isDirty('rut')) {
                $tenant->rut = Rut::normalize($tenant->rut);
            }

            if ($tenant->isDirty('name') && ! $tenant->isDirty('slug')) {
                $tenant->slug = static::generateUniqueSlug($tenant->name, (int) $tenant->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'tenant';
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Empresa (razón social / RUT) vinculada en relación 1:1 con este tenant.
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'tenant_id');
    }

    /**
     * Compatibilidad con consultas de colección; bajo ADR-001 un Tenant posee a lo sumo 1 Company.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'tenant_id');
    }

    /**
     * Usuarios asociados a este tenant.
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

