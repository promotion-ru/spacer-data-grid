<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataGridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'image'        => 'nullable|array',
            'image.data'   => 'required_with:image|string',
            'image.name'   => 'required_with:image|string',
            'image.type'   => 'required_with:image|string|in:image/jpeg,image/png,image/gif,image/webp',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'Название таблицы обязательно для заполнения',
            'name.max'                 => 'Название таблицы не должно превышать 255 символов',
            'description.max'          => 'Описание не должно превышать 1000 символов',
            'image.array'              => 'Поле image должно быть массивом.',
            'image.data.required_with' => 'Поле данные аватара обязательно при загрузке аватара.',
            'image.data.string'        => 'Поле данные аватара должно быть строкой.',
            'image.name.required_with' => 'Поле имя аватара обязательно при загрузке аватара.',
            'image.name.string'        => 'Поле имя аватара должно быть строкой.',
            'image.type.required_with' => 'Поле тип аватара обязательно при загрузке аватара.',
            'image.type.string'        => 'Поле тип аватара должно быть строкой.',
            'image.type.in'            => 'Поле тип аватара должно быть одним из: image/jpeg, image/png, image/gif, image/webp.',
        ];
    }
}
