<?php
// app/Http/Requests/InviteUserRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteUserRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'email'         => ['required', 'email', 'max:255'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'in:view,create,update,delete'],
        ];
    }

    public function messages()
    {
        return [
            'email.required'       => 'Email обязателен для заполнения',
            'email.email'          => 'Введите корректный email',
            'permissions.required' => 'Выберите хотя бы одно разрешение',
            'permissions.min'      => 'Выберите хотя бы одно разрешение',
            'permissions.*.in'     => 'Недопустимое разрешение',
        ];
    }
}
