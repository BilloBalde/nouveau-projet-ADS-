<?php

namespace App\Http\Requests\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'city' => 'required|string|max:255',
            'phone' => 'required|string',
            'full_address' => 'required|string',
            'instructions' => 'nullable|string',
        ];
    }
}
