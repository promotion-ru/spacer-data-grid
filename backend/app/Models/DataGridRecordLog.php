<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataGridRecordLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_grid_id',
        'data_grid_record_id',
        'action',
        'user_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
    ];

    public function dataGrid(): BelongsTo
    {
        return $this->belongsTo(DataGrid::class);
    }

    public function dataGridRecord(): BelongsTo
    {
        return $this->belongsTo(DataGridRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedChangesAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        $labels = $this->getFieldLabels();

        foreach ($this->new_values as $key => $newValue) {
            if (isset($this->old_values[$key]) && $this->old_values[$key] !== $newValue) {
                $changes[] = [
                    'field'     => $labels[$key] ?? $key,
                    'old_value' => $this->formatValue($this->old_values[$key], $key),
                    'new_value' => $this->formatValue($newValue, $key),
                ];
            }
        }

        return $changes;
    }

    private function getFieldLabels(): array
    {
        return [
            // Записи
            'name'        => 'Название',
            'description' => 'Описание',
            'title'       => 'Заголовок',
            'content'     => 'Содержание',
            'status'      => 'Статус',
            'priority'    => 'Приоритет',
            'attachments' => 'Вложения',

            // Типы
            'is_global'   => 'Глобальный тип',
        ];
    }

    private function formatValue($value, string $field = null): string
    {
        if (is_null($value)) {
            return 'Не указано';
        }

        if (is_bool($value)) {
            return $value ? 'Да' : 'Нет';
        }

        if ($field === 'attachments' && is_array($value)) {
            return implode(', ', $value);
        }

        return (string)$value;
    }

    public function getActionBadgeAttribute(): array
    {
        $badges = [
            // Записи
            'record_created'     => ['text' => 'Запись создана', 'severity' => 'success'],
            'record_updated'     => ['text' => 'Запись обновлена', 'severity' => 'info'],
            'record_deleted'     => ['text' => 'Запись удалена', 'severity' => 'danger'],
            'attachment_added'   => ['text' => 'Вложение добавлено', 'severity' => 'info'],
            'attachment_removed' => ['text' => 'Вложение удалено', 'severity' => 'warning'],

            // Типы данных
            'type_created'       => ['text' => 'Тип создан', 'severity' => 'success'],
            'type_deleted'       => ['text' => 'Тип удален', 'severity' => 'danger'],
        ];

        return $badges[$this->action] ?? ['text' => 'Действие', 'severity' => 'secondary'];
    }
}
