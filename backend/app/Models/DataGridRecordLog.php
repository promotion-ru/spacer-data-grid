<?php

namespace App\Models;

use App\Helpers\Constant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property string $data_grid_id
 * @property int|null $data_grid_record_id
 * @property string $action
 * @property int|null $user_id
 * @property string $description
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DataGrid $dataGrid
 * @property-read \App\Models\DataGridRecord|null $dataGridRecord
 * @property-read array $action_badge
 * @property-read array $formatted_changes
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereDataGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereDataGridRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordLog whereUserId($value)
 * @mixin \Eloquent
 */
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
            'name'              => 'Название',
            'date'              => 'Дата',
            'operation_type_id' => 'Тип операции',
            'type_id'           => 'Тип записи',
            'amount'            => 'Сумма',
            'description'       => 'Описание',
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

        if ($field === 'operation_type_id') {
            if ($value == Constant::OPERATION_TYPE_INCOME) {
                return 'Доход';
            }
            if ($value == Constant::OPERATION_TYPE_EXPENSE) {
                return 'Расход';
            }
        }

        if ($field === 'type_id') {
            $type = DataGridType::query()->where('id', $value)->first();
            if ($type) {
                return $type->name;
            }
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
        ];

        return $badges[$this->action] ?? ['text' => 'Действие', 'severity' => 'secondary'];
    }
}
