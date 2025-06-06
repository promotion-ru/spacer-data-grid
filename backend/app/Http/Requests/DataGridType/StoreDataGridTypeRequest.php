<?php

namespace App\Http\Requests\DataGridType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataGridTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'         => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('data_grid_types')->where(function ($query) {
                    return $query->where('data_grid_id', $this->data_grid_id)
                        ->orWhere('is_global', true);
                })
            ],
            'data_grid_id' => [
                'required',
                'string',
                'exists:data_grids,id'
            ],
            'description'  => [
                'nullable',
                'string',
                'max:1000'
            ],
            'is_global'    => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Название типа обязательно',
            'name.min'              => 'Название типа должно содержать минимум 2 символа',
            'name.max'              => 'Название типа не должно превышать 255 символов',
            'name.unique'           => 'Тип с таким названием уже существует для данной таблицы',
            'data_grid_id.required' => 'ID таблицы данных обязателен',
            'data_grid_id.exists'   => 'Указанная таблица данных не существует',
            'description.max'       => 'Описание не должно превышать 1000 символов',
            'is_global.boolean'     => 'Поле "Глобальный" должно быть логическим значением'
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_global' => $this->is_global ?? false,
            'name'      => trim($this->name)
        ]);
    }
}
