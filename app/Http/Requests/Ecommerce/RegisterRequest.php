<?php

namespace App\Http\Requests\Ecommerce;

use App\Services\AuthService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Le numéro est ramené à sa forme locale avant toute vérification.
     *
     * Sans cela, la règle `unique` comparerait « +224 622 33 44 55 » aux
     * « 622334455 » déjà en base sans y voir de doublon, et le client se
     * retrouverait avec un second compte.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => AuthService::normaliserTelephone($this->input('phone'))]);
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', AuthService::REGLE_TELEPHONE, 'unique:users,phone'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Votre nom est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => AuthService::MESSAGE_TELEPHONE,
            'phone.unique' => 'Ce numéro a déjà un compte. Connectez-vous plutôt.',
        ];
    }
}
