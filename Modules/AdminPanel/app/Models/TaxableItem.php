<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\AdminPanel\Database\Factories\TaxableItemFactory;

class TaxableItem extends Model
{
    use HasFactory;

    protected $table = 'taxable_items_catalog';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    /**
     * Relación con empresas
     */
    public function companies()
    {
        return $this->belongsToMany(\Modules\Companies\Models\Company::class, 'company_taxable_item', 'taxable_item_id', 'company_id')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    // protected static function newFactory(): TaxableItemFactory
    // {
    //     // return TaxableItemFactory::new();
    // }
}
