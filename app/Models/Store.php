<?php

namespace App\Models;

use App\Policies\StorePolicy;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['delivery_options', 'cash_enabled', 'bank_transfer_enabled', 'name', 'slug', 'tagline', 'is_listed', 'category_id', 'province', 'city', 'district', 'province_code', 'city_code', 'district_code', 'address_line', 'latitude', 'longitude', 'show_full_address', 'pickup_enabled', 'pickup_instructions', 'delivery_enabled', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km', 'delivery_min_distance_m', 'delivery_max_distance_m'])]
#[UsePolicy(StorePolicy::class)]
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $table = 'rintis_stores';

    protected static function booted(): void
    {
        static::saving(function (Store $store): void {
            $store->name = Str::squish($store->name);
            $store->name_key = self::nameKey($store->name);
        });
    }

    public static function nameKey(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    public function settingsReady(): bool
    {
        $locationReady = $this->category_id && $this->province && $this->city && $this->district && $this->address_line
            && $this->latitude !== null && $this->longitude !== null;
        $deliveryReady = ! $this->delivery_enabled || ($this->delivery_min_distance_m > 0
            && $this->delivery_max_distance_m >= $this->delivery_min_distance_m
            && (($this->delivery_fee_mode === 'fixed' && $this->delivery_fixed_fee !== null)
                || ($this->delivery_fee_mode === 'road_distance' && $this->delivery_rate_per_km !== null)));

        return (bool) ($locationReady && ($this->pickup_enabled || $this->delivery_enabled) && $deliveryReady);
    }

    public function completeSetupIfReady(): bool
    {
        return DB::transaction(function (): bool {
            $store = self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($store->setup_completed_at !== null || ! $store->settingsReady()
                || ! $store->catalogCategories()->exists()
                || ! $store->catalogProducts()->where('is_visible', true)->exists()) {
                return false;
            }
            $store->setup_completed_at = now();
            $store->save();
            $this->setup_completed_at = $store->setup_completed_at;

            return true;
        });
    }

    protected function casts(): array
    {
        return ['delivery_options' => 'array', 'cash_enabled' => 'boolean', 'bank_transfer_enabled' => 'boolean', 'setup_completed_at' => 'datetime', 'pickup_enabled' => 'boolean', 'delivery_enabled' => 'boolean', 'is_listed' => 'boolean', 'show_full_address' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function catalogCategories(): HasMany
    {
        return $this->hasMany(CatalogCategory::class);
    }

    public function openOrders(): HasMany
    {
        return $this->hasMany(OpenOrder::class);
    }

    public function catalogProducts(): HasMany
    {
        return $this->hasMany(CatalogProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function publicProfile(): array
    {
        return [
            ...$this->only('name', 'tagline', 'province', 'city'),
            'category' => $this->category?->name,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
