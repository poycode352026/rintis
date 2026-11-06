<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'catalog_product_id', 'line_index', 'rating'])]
class ProductReview extends Model
{
    protected $table = 'rintis_product_reviews';

    protected function casts(): array
    {
        return ['rating' => 'integer', 'line_index' => 'integer'];
    }
}
