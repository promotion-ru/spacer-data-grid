<?php

namespace App\Http\Requests\DataGridType;

use Illuminate\Foundation\Http\FormRequest;

class SearchDataGridTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'data_grid_id' => [
                'required',
                'string',
                'exists:data_grids,id'
            ],
            'search'       => [
                'nullable',
                'string',
                'max:255',
                'min:1'
            ],
            'limit'        => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'data_grid_id.required' => 'ID таблицы данных обязателен',
            'data_grid_id.exists'   => 'Указанная таблица данных не существует',
            'search.max'            => 'Поисковый запрос не должен превышать 255 символов',
            'search.min'            => 'Поисковый запрос должен содержать минимум 1 символ',
            'limit.min'             => 'Лимит должен быть не менее 1',
            'limit.max'             => 'Лимит не должен превышать 100'
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'limit' => $this->limit ?? 20
        ]);
    }
}
