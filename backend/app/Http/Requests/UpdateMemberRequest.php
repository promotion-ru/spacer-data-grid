<?php
// app/Http/Requests/UpdateMemberRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'in:view,create,update,delete'],
        ];
    }

    public function messages()
    {
        return [
            'permissions.required' => 'Выберите хотя бы одно разрешение',
            'permissions.min'      => 'Выберите хотя бы одно разрешение',
            'permissions.*.in'     => 'Недопустимое разрешение',
        ];
    }
}
