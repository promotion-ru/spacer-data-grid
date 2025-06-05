<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataGridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];

        if ($this->has('image')) {
            $rules['image'] = 'required|array';
            $rules['image.data'] = 'required|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Название таблицы обязательно для заполнения',
            'name.max'            => 'Название таблицы не должно превышать 255 символов',
            'description.max'     => 'Описание не должно превышать 1000 символов',
            'image.required'      => 'Изображение обязательно',
            'image.array'         => 'Изображение должно быть объектом',
            'image.data.required' => 'Данные изображения обязательны',
            'image.data.string'   => 'Данные изображения должны быть строкой',
        ];
    }
}
