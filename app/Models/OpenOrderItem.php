<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['catalog_product_id', 'name', 'description', 'price', 'quota', 'options', 'costing', 'image_path'])]
#[Hidden(['costing', 'options'])]
class OpenOrderItem extends Model
{
    protected $table = 'rintis_open_order_items';

    protected function casts(): array
    {
        return ['price' => 'integer', 'quota' => 'integer', 'options' => 'array', 'costing' => 'array'];
    }

    public function openOrder(): BelongsTo
    {
        return $this->belongsTo(OpenOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id')->withTrashed();
    }

    public function publicDetails(): array
    {
        return ['remaining' => $this->quota === null ? null : max(0, $this->quota - $this->ordered_quantity), 'imageUrl' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null, ...$this->only('id', 'name', 'description', 'price', 'quota'), 'options' => array_map(fn (array $group): array => [
            'id' => $group['id'], 'name' => $group['name'], 'required' => $group['required'], 'selection' => $group['selection'],
            'choices' => array_map(fn (array $choice): array => array_intersect_key($choice, array_flip(['id', 'name', 'price_adjustment'])), $group['choices']),
        ], $this->options ?? [])];
    }
}
