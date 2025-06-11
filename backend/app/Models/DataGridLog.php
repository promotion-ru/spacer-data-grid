<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property string $data_grid_id
 * @property string $action
 * @property int|null $user_id
 * @property int|null $target_user_id
 * @property string $description
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DataGrid $dataGrid
 * @property-read array $action_badge
 * @property-read array $formatted_changes
 * @property-read \App\Models\User|null $target_user
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereDataGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereTargetUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridLog whereUserId($value)
 * @mixin \Eloquent
 */
class DataGridLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_grid_id',
        'action',
        'user_id',
        'target_user_id',
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

    public function target_user(): BelongsTo
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
            'name'        => 'Название',
            'description' => 'Описание',
            'is_active'   => 'Активность',
            'image_id'    => 'Изображение',
            'permissions' => 'Права доступа',
            'email'       => 'Email',
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

        if ($field === 'permissions' && is_array($value)) {
            $labels = [
                'view'   => 'Просмотр',
                'create' => 'Создание',
                'update' => 'Редактирование',
                'delete' => 'Удаление',
                'manage' => 'Управление'
            ];
            return implode(', ', array_map(fn($p) => $labels[$p] ?? $p, $value));
        }

        if ($field === 'image_id' && $value) {
            return 'Изображение загружено';
        }

        return (string)$value;
    }

    public function getActionBadgeAttribute(): array
    {
        $badges = [
            'created'              => ['text' => 'Создание', 'severity' => 'success'],
            'updated'              => ['text' => 'Обновление', 'severity' => 'info'],
            'deleted'              => ['text' => 'Удаление', 'severity' => 'danger'],
            'member_added'         => ['text' => 'Участник добавлен', 'severity' => 'success'],
            'member_removed'       => ['text' => 'Участник удален', 'severity' => 'warning'],
            'member_updated'       => ['text' => 'Права изменены', 'severity' => 'info'],
            'member_left'          => ['text' => 'Покинул таблицу', 'severity' => 'warning'],
            'invitation_sent'      => ['text' => 'Приглашение отправлено', 'severity' => 'info'],
            'invitation_accepted'  => ['text' => 'Приглашение принято', 'severity' => 'success'],
            'invitation_declined'  => ['text' => 'Приглашение отклонено', 'severity' => 'warning'],
            'invitation_cancelled' => ['text' => 'Приглашение отменено', 'severity' => 'secondary'],
        ];

        return $badges[$this->action] ?? ['text' => 'Действие', 'severity' => 'secondary'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
