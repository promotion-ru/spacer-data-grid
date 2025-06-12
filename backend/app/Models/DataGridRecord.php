<?php
// app/Models/GridRecord.php
namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 *
 *
 * @property int $id
 * @property string $data_grid_id
 * @property string $name
 * @property string $date Дата операции
 * @property int|null $operation_type_id
 * @property int|null $type_id
 * @property string|null $description
 * @property int|null $amount Сумма операции
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaCollection<int, Media> $attachments
 * @property-read int|null $attachments_count
 * @property-read User $creator
 * @property-read DataGrid $dataGrid
 * @property-read Collection<int, DataGridRecordMedia> $dataGridRecordMedia
 * @property-read int|null $data_grid_record_media_count
 * @property-read Collection<int, DataGridRecordLog> $logs
 * @property-read int|null $logs_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read DataGridType|null $type
 * @method static Builder<static>|DataGridRecord amountBetween($from = null, $to = null)
 * @method static Builder<static>|DataGridRecord amountGreaterThan($amount)
 * @method static Builder<static>|DataGridRecord amountLessThan($amount)
 * @method static Builder<static>|DataGridRecord byOperationType($operationTypeId)
 * @method static Builder<static>|DataGridRecord byOwner($ownerType, $currentUserId = null)
 * @method static Builder<static>|DataGridRecord byRecordType($typeId)
 * @method static Builder<static>|DataGridRecord changedInDays($days = 1)
 * @method static Builder<static>|DataGridRecord createdBetween($from = null, $to = null)
 * @method static Builder<static>|DataGridRecord expense()
 * @method static Builder<static>|DataGridRecord income()
 * @method static Builder<static>|DataGridRecord my($userId = null)
 * @method static Builder<static>|DataGridRecord newModelQuery()
 * @method static Builder<static>|DataGridRecord newQuery()
 * @method static Builder<static>|DataGridRecord notMy($userId = null)
 * @method static Builder<static>|DataGridRecord operationDateBetween($from = null, $to = null)
 * @method static Builder<static>|DataGridRecord query()
 * @method static Builder<static>|DataGridRecord search($search)
 * @method static Builder<static>|DataGridRecord whereAmount($value)
 * @method static Builder<static>|DataGridRecord whereCreatedAt($value)
 * @method static Builder<static>|DataGridRecord whereCreatedBy($value)
 * @method static Builder<static>|DataGridRecord whereDataGridId($value)
 * @method static Builder<static>|DataGridRecord whereDate($value)
 * @method static Builder<static>|DataGridRecord whereDescription($value)
 * @method static Builder<static>|DataGridRecord whereId($value)
 * @method static Builder<static>|DataGridRecord whereName($value)
 * @method static Builder<static>|DataGridRecord whereOperationTypeId($value)
 * @method static Builder<static>|DataGridRecord whereTypeId($value)
 * @method static Builder<static>|DataGridRecord whereUpdatedAt($value)
 * @method static Builder<static>|DataGridRecord withAttachments($filter)
 * @mixin Eloquent
 */
class DataGridRecord extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'data_grid_id',
        'name',
        'date',
        'operation_type_id',
        'type_id',
        'description',
        'amount',
        'created_by',
    ];

    protected $with = ['creator'];

    public function dataGrid(): BelongsTo
    {
        return $this->belongsTo(DataGrid::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DataGridType::class);
    }

    public function dataGridRecordMedia(): HasMany
    {
        return $this->hasMany(DataGridRecordMedia::class);
    }


    /**
     * Определяет отношение для медиафайлов, принадлежащих коллекции 'attachments'.
     * Это позволяет использовать ->with('attachments') для "жадной" загрузки.
     *
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(config('media-library.media_model'), 'model')
            ->where('collection_name', 'attachments');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/svg+xml', 'image/tiff',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/json',
                'text/xml',
                'application/xml',
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/gzip',
                'application/x-tar',
            ]);
    }

    public function images(): BelongsToMany
    {
        return $this->media()->wherePivot('media_type', 'image');
    }

    public function logs()
    {
        return $this->hasMany(DataGridRecordLog::class, 'data_grid_id');
    }

    /**
     * Scope для поиска по названию, описанию и автору
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhereHas('creator', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
        });
    }

    /**
     * Scope для фильтрации по владельцу записи
     */
    public function scopeByOwner($query, $ownerType, $currentUserId = null)
    {
        if (!$currentUserId) {
            return $query;
        }

        if ($ownerType === 'my') {
            return $query->where('created_by', $currentUserId);
        } elseif ($ownerType === 'not_my') {
            return $query->where('created_by', '!=', $currentUserId);
        }

        return $query;
    }

    /**
     * Scope для фильтрации по типу операции
     */
    public function scopeByOperationType($query, $operationTypeId)
    {
        return $query->where('operation_type_id', $operationTypeId);
    }

    /**
     * Scope для фильтрации по типу записи
     */
    public function scopeByRecordType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope для фильтрации по наличию вложений
     */
    public function scopeWithAttachments($query, $filter)
    {
        if ($filter === 'with') {
            return $query->whereHas('attachments');
        } elseif ($filter === 'without') {
            return $query->whereDoesntHave('attachments');
        }

        return $query;
    }

    /**
     * Scope для фильтрации по дате создания
     */
    public function scopeCreatedBetween($query, $from = null, $to = null)
    {
        // Применяем фильтр только если переданы оба значения
        if ($from && $to) {
            $query->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope для фильтрации по дате операции
     */
    public function scopeOperationDateBetween($query, $from = null, $to = null)
    {
        // Применяем фильтр только если переданы оба значения
        if ($from && $to) {
            $query->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope для фильтрации по сумме
     */
    public function scopeAmountBetween($query, $from = null, $to = null)
    {
        // Применяем фильтр только если переданы оба значения
        if ($from !== null && $to !== null) {
            $query->where('amount', '>=', $from)
                ->where('amount', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope для фильтрации по от суммы
     */
    public function scopeAmountFrom($query, $from = null, )
    {
        if ($from !== null) {
            $query->where('amount', '>=', $from);
        }

        return $query;
    }

    /**
     * Scope для фильтрации по до суммы
     */
    public function scopeAmountTo($query, $to = null)
    {
        if ($to !== null) {
            $query->where('amount', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope для записей, измененных за определенный период
     */
    public function scopeChangedInDays($query, $days = 1)
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    /**
     * Scope для записей текущего пользователя
     */
    public function scopeMy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('created_by', $userId);
    }

    /**
     * Scope для записей других пользователей
     */
    public function scopeNotMy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('created_by', '!=', $userId);
    }

    /**
     * Scope для получения записей доходов
     */
    public function scopeIncome($query)
    {
        return $query->where('operation_type_id', 1);
    }

    /**
     * Scope для получения записей расходов
     */
    public function scopeExpense($query)
    {
        return $query->where('operation_type_id', 2);
    }

    /**
     * Scope для получения записей с определенной суммой больше указанной
     */
    public function scopeAmountGreaterThan($query, $amount)
    {
        return $query->where('amount', '>', $amount);
    }

    /**
     * Scope для получения записей с определенной суммой меньше указанной
     */
    public function scopeAmountLessThan($query, $amount)
    {
        return $query->where('amount', '<', $amount);
    }
}
