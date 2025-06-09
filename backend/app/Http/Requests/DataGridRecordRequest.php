<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use DateTime;
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
            'name'                   => 'required|string|max:255',
            'date'                   => 'required|date',
            'operation_type_id'      => 'required|integer|in:1,2',
            'type_id'                => 'required|integer|exists:data_grid_types,id',
            'description'            => 'nullable|string|max:2000',
            'amount'                 => 'required|numeric|min:0|max:999999999',
            'new_attachments'        => 'nullable|array|max:10',
            'new_attachments.*'      => 'array',
            'new_attachments.*.data' => 'required_with:new_attachments.*|string',
            'new_attachments.*.name' => 'required_with:new_attachments.*|string|max:255',
            'new_attachments.*.type' => 'required_with:new_attachments.*|string',
            'new_attachments.*.size' => 'required_with:new_attachments.*|integer|max:10485760',
        ];

        // Для обновления добавляем валидацию удаления вложений
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['remove_attachments'] = 'nullable|array';
            $rules['remove_attachments.*'] = 'integer|exists:media,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'                        => 'Название записи обязательно для заполнения',
            'name.max'                             => 'Название записи не должно превышать 255 символов',
            'date.required'                        => 'Дата обязательна для заполнения',
            'date.date'                            => 'Дата должна быть корректной датой',
            'operation_type_id.required'           => 'Тип операции обязателен для выбора',
            'operation_type_id.integer'            => 'Тип операции должен быть числом',
            'operation_type_id.in'                 => 'Тип операции должен быть 1 (доход) или 2 (расход)',
            'type_id.required'                     => 'Тип записи обязателен для выбора',
            'type_id.integer'                      => 'Тип записи должен быть числом',
            'type_id.exists'                       => 'Выбранный тип записи не существует',
            'description.max'                      => 'Описание не должно превышать 2000 символов',
            'amount.required'                      => 'Сумма обязательна для заполнения',
            'amount.numeric'                       => 'Сумма должна быть числом',
            'amount.min'                           => 'Сумма должна быть больше 0',
            'amount.max'                           => 'Сумма не должна превышать 999 999 999.99',
            'new_attachments.array'                => 'Новые вложения должны быть массивом',
            'new_attachments.max'                  => 'Максимум 10 новых файлов можно загрузить',
            'new_attachments.*.array'              => 'Каждое новое вложение должно быть массивом',
            'new_attachments.*.data.required_with' => 'Данные файла обязательны',
            'new_attachments.*.data.string'        => 'Данные файла должны быть строкой',
            'new_attachments.*.name.required_with' => 'Имя файла обязательно',
            'new_attachments.*.name.string'        => 'Имя файла должно быть строкой',
            'new_attachments.*.name.max'           => 'Имя файла не должно превышать 255 символов',
            'new_attachments.*.type.required_with' => 'Тип файла обязателен',
            'new_attachments.*.type.string'        => 'Тип файла должен быть строкой',
            'new_attachments.*.size.required_with' => 'Размер файла обязателен',
            'new_attachments.*.size.integer'       => 'Размер файла должен быть числом',
            'new_attachments.*.size.max'           => 'Размер файла не должен превышать 10MB',
            'remove_attachments.array'             => 'Список файлов для удаления должен быть массивом',
            'remove_attachments.*.integer'         => 'ID файла для удаления должен быть числом',
            'remove_attachments.*.exists'          => 'Файл для удаления не найден',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Убедимся, что amount это число с плавающей точкой
        if ($this->has('amount') && is_string($this->amount)) {
            $this->merge([
                'amount' => (int)$this->amount
            ]);
        }

        // Преобразуем дату из формата d.m.Y в формат Y-m-d для базы данных
        if ($this->has('date') && is_string($this->date)) {
            $date = Carbon::parse($this->date);
            if ($date) {
                $this->merge([
                    'date' => $date->format('Y-m-d')
                ]);
            }
        }
    }
}
