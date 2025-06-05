<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'          => 'required|string|max:255',
            'surname'       => 'nullable|string|max:255',
            'email'         => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password'      => 'nullable|string|min:8|confirmed',
            'avatar'        => 'nullable|array',
            'avatar.data'   => 'required_with:avatar|string',
            'avatar.name'   => 'required_with:avatar|string',
            'avatar.type'   => 'required_with:avatar|string|in:image/jpeg,image/png,image/gif,image/webp',
            'delete_avatar' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'Поле "Имя" обязательно для заполнения',
            'email.required'            => 'Поле "Email" обязательно для заполнения',
            'email.email'               => 'Поле "Email" должно быть корректным email адресом',
            'email.unique'              => 'Пользователь с таким email уже существует',
            'password.min'              => 'Пароль должен содержать минимум 8 символов',
            'password.confirmed'        => 'Подтверждение пароля не совпадает',
            'avatar.array'              => 'Поле avatar должно быть массивом.',
            'avatar.data.required_with' => 'Поле данные аватара обязательно при загрузке аватара.',
            'avatar.data.string'        => 'Поле данные аватара должно быть строкой.',
            'avatar.name.required_with' => 'Поле имя аватара обязательно при загрузке аватара.',
            'avatar.name.string'        => 'Поле имя аватара должно быть строкой.',
            'avatar.type.required_with' => 'Поле тип аватара обязательно при загрузке аватара.',
            'avatar.type.string'        => 'Поле тип аватара должно быть строкой.',
            'avatar.type.in'            => 'Поле тип аватара должно быть одним из: image/jpeg, image/png, image/gif, image/webp.',
        ];
    }
}
