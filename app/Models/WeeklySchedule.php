<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['store_id', 'enabled', 'days', 'product_ids', 'last_date', 'skip_date', 'last_error'])]
class WeeklySchedule extends Model
{
    protected $table = 'rintis_weekly_schedules';

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'days' => 'array', 'product_ids' => 'array'];
    }
}
