<?php

namespace App\Http\Requests\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
        ];
    }

    public function messages()
    {
        return [
            'phone.unique' => 'Ce numéro est déjà utilisé.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.'
        ];
    }
}
