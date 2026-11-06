<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['store_id', 'status', 'stops', 'origin', 'distance_m'])]
class DeliveryRun extends Model
{
    protected $table = 'rintis_delivery_runs';

    protected function casts(): array
    {
        return ['stops' => 'array', 'origin' => 'array', 'distance_m' => 'integer'];
    }
}
