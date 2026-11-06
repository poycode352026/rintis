<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // The controller checks the private receipt token and completed order.
    }

    public function rules(): array
    {
        return [
            'reviews' => ['nullable', 'array', 'min:1', 'max:50'],
            'reviews.*' => ['array:line_index,rating'],
            'reviews.*.line_index' => ['required', 'integer', 'min:0', 'distinct'],
            'reviews.*.rating' => ['required', 'integer', 'between:1,5'],
            'line_index' => ['required_without:reviews', 'integer', 'min:0'],
            'rating' => ['required_without:reviews', 'integer', 'between:1,5'],
        ];
    }
}
