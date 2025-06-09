<?php
// app/Models/GridRecord.php
namespace App\Models;

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
 * @property int $created_by
 * @property string $name
 * @property int|null $operation_type_id
 * @property int|null $type_id
 * @property string|null $description
 * @property int|null $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $creator
 * @property-read DataGrid $dataGrid
 * @property-read Collection<int, DataGridRecordMedia> $dataGridRecordMedia
 * @property-read MediaCollection<int, Media> $media
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
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'application/zip',
                'application/x-rar-compressed',
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
}
