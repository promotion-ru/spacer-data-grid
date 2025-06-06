<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataGridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'new_image'        => 'nullable|array',
            'new_image.data'   => 'required_with:new_image|string',
            'new_image.name'   => 'required_with:new_image|string',
            'new_image.type'   => 'required_with:new_image|string|in:image/jpeg,image/png,image/gif,image/webp',
            'delete_new_image' => 'nullable|boolean',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'Название таблицы обязательно для заполнения',
            'name.max'                     => 'Название таблицы не должно превышать 255 символов',
            'description.max'              => 'Описание не должно превышать 1000 символов',
            'new_image.array'              => 'Поле new_image должно быть массивом.',
            'new_image.data.required_with' => 'Поле данные аватара обязательно при загрузке аватара.',
            'new_image.data.string'        => 'Поле данные аватара должно быть строкой.',
            'new_image.name.required_with' => 'Поле имя аватара обязательно при загрузке аватара.',
            'new_image.name.string'        => 'Поле имя аватара должно быть строкой.',
            'new_image.type.required_with' => 'Поле тип аватара обязательно при загрузке аватара.',
            'new_image.type.string'        => 'Поле тип аватара должно быть строкой.',
            'new_image.type.in'            => 'Поле тип аватара должно быть одним из: image/jpeg, image/png, image/gif, image/webp.',
        ];
    }
}
