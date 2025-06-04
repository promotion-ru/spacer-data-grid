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

        if ($this->hasFile('image')) {
            $rules['image'] = 'image|mimes:jpeg,png,webp|max:5120';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Название таблицы обязательно для заполнения',
            'name.max'        => 'Название таблицы не должно превышать 255 символов',
            'description.max' => 'Описание не должно превышать 1000 символов',
            'image.image'     => 'Файл должен быть изображением',
            'image.mimes'     => 'Поддерживаемые форматы: JPEG, PNG, WebP',
            'image.max'       => 'Размер изображения не должен превышать 5MB',
        ];
    }
}
