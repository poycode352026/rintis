<?php

namespace App\Models;

use Database\Factories\CatalogProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'description', 'price', 'catalog_category_id', 'is_visible', 'image_path', 'costing', 'options'])]
#[Hidden(['costing', 'options'])]
class CatalogProduct extends Model
{
    /** @use HasFactory<CatalogProductFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'rintis_catalog_products';

    protected function casts(): array
    {
        return ['options' => 'array', 'costing' => 'array', 'price' => 'integer', 'is_visible' => 'boolean'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function catalogCategory(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function sellingItems(): HasMany
    {
        return $this->hasMany(OpenOrderItem::class)->whereHas('openOrder', fn ($query) => $query->where('status', 'published')->where('closes_at', '>', now()));
    }

    public function publicOptions(): array
    {
        return array_map(fn (array $group): array => [
            'name' => $group['name'], 'selection' => $group['selection'], 'required' => $group['required'],
            'choices' => array_map(fn (array $choice): array => ['name' => $choice['name']], $group['choices']),
        ], $this->options ?? []);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
