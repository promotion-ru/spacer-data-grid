<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataGridRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];

        if ($this->has('attachments')) {
            $rules['attachments'] = 'array|max:10';
            $rules['attachments.*'] = 'required|array';
            $rules['attachments.*.data'] = 'required|string';
        }

        if ($this->has('remove_attachments')) {
            $rules['remove_attachments'] = 'array';
            $rules['remove_attachments.*'] = 'integer|exists:media,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'Название записи обязательно для заполнения',
            'name.max'                    => 'Название записи не должно превышать 255 символов',
            'description.max'             => 'Описание не должно превышать 2000 символов',
            'attachments.max'             => 'Можно загрузить максимум 10 файлов',
            'attachments.*.required'      => 'Каждый вложенный файл обязателен',
            'attachments.*.array'         => 'Каждый элемент должен быть объектом',
            'attachments.*.data.required' => 'Данные файла обязательны',
            'attachments.*.data.string'   => 'Данные файла должны быть строкой',
        ];
    }
}
