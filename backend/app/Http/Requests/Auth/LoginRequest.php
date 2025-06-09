<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => 'required|email|max:255',
            'password'    => 'required|string|min:1|max:255',
            'device_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email обязателен для заполнения.',
            'email.email'       => 'Email должен быть корректным адресом электронной почты.',
            'password.required' => 'Пароль обязателен для заполнения.',
        ];
    }
}
