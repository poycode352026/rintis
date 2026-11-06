<?php

namespace App\Models;

use Database\Factories\CatalogCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class CatalogCategory extends Model
{
    /** @use HasFactory<CatalogCategoryFactory> */
    use HasFactory;

    protected $table = 'rintis_catalog_categories';

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class, 'catalog_category_id');
    }
}
