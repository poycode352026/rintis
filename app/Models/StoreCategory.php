<?php

namespace App\Models;

use Database\Factories\StoreCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug'])]
class StoreCategory extends Model
{
    /** @use HasFactory<StoreCategoryFactory> */
    use HasFactory;

    protected $table = 'rintis_store_categories';
}
