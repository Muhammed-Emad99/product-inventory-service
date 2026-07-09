<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->route('id')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'stock_quantity' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],
            'low_stock_threshold' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }
}
