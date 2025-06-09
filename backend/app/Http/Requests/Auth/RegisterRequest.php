<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|min:2|max:255',
            'email'       => 'required|string|email|max:255|unique:users,email',
            'password'    => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Имя обязательно для заполнения.',
            'name.min'           => 'Имя должно содержать минимум 2 символа.',
            'email.required'     => 'Email обязателен для заполнения.',
            'email.email'        => 'Email должен быть корректным адресом электронной почты.',
            'email.unique'       => 'Пользователь с таким email уже существует.',
            'password.required'  => 'Пароль обязателен для заполнения.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',
        ];
    }
}
