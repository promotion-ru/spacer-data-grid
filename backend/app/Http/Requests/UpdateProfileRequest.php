<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = Auth::user();

        $rules = [
            'name'          => 'required|string|max:255',
            'surname'       => 'nullable|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => ['nullable', 'confirmed', Password::min(8)],
            'avatar'        => 'nullable|array',
            'avatar.data'   => 'required_with:avatar|string',
            'avatar.name'   => 'required_with:avatar|string',
            'avatar.type'   => 'required_with:avatar|string|in:image/jpeg,image/png,image/gif,image/webp',
            'delete_avatar' => 'nullable|boolean'
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле имя обязательно для заполнения.',
            'name.string'   => 'Поле имя должно быть строкой.',
            'name.max'      => 'Поле имя не может содержать более 255 символов.',

            'surname.string' => 'Поле фамилия должно быть строкой.',
            'surname.max'    => 'Поле фамилия не может содержать более 255 символов.',

            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email'    => 'Поле email должно быть действительным email адресом.',
            'email.unique'   => 'Пользователь с таким email уже существует.',

            'password.confirmed' => 'Подтверждение пароля не совпадает.',
            'password.min'       => 'Пароль должен содержать минимум 8 символов.',

            'avatar.array'              => 'Поле avatar должно быть массивом.',
            'avatar.data.required_with' => 'Поле данные аватара обязательно при загрузке аватара.',
            'avatar.data.string'        => 'Поле данные аватара должно быть строкой.',
            'avatar.name.required_with' => 'Поле имя аватара обязательно при загрузке аватара.',
            'avatar.name.string'        => 'Поле имя аватара должно быть строкой.',
            'avatar.type.required_with' => 'Поле тип аватара обязательно при загрузке аватара.',
            'avatar.type.string'        => 'Поле тип аватара должно быть строкой.',
            'avatar.type.in'            => 'Поле тип аватара должно быть одним из: image/jpeg, image/png, image/gif, image/webp.',

            'delete_avatar.boolean' => 'Поле удаление аватара должно быть булевым значением.'
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'имя',
            'surname'       => 'фамилия',
            'email'         => 'электронная почта',
            'password'      => 'пароль',
            'avatar'        => 'аватар',
            'avatar.data'   => 'данные аватара',
            'avatar.name'   => 'имя файла аватара',
            'avatar.type'   => 'тип файла аватара',
            'delete_avatar' => 'удаление аватара'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Ошибка валидации',
            'errors'  => $validator->errors()
        ], 422);

        throw new HttpResponseException($response);
    }
}
