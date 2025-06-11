<?php

namespace App\Http\Requests\DataGridType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataGridTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация в контроллере через Policy
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('data_grid_types')
                    ->where('data_grid_id', $this->input('data_grid_id'))
            ],
            'data_grid_id' => 'required|exists:data_grids,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название типа обязательно для заполнения',
            'name.string' => 'Название типа должно быть строкой',
            'name.max' => 'Название типа не должно превышать 255 символов',
            'name.unique' => 'Тип с таким названием уже существует в данной таблице',
            'data_grid_id.required' => 'Идентификатор таблицы обязателен',
            'data_grid_id.exists' => 'Указанная таблица не существует',
        ];
    }
}
