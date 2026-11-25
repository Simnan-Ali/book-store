<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inventory.update');
    }

    public function rules(): array
    {
        return [
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'low_stock_alert' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
