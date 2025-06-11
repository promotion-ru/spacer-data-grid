<?php
// app/Models/RecordMedia.php
namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 
 *
 * @property int $id
 * @property int $data_grid_record_id
 * @property int $media_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\DataGridRecord $dataGridRecord
 * @property-read Media $media
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia whereDataGridRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridRecordMedia whereUpdatedAt($value)
 * @mixin Eloquent
 */
class DataGridRecordMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_grid_record_id',
        'media_id',
    ];

    public function dataGridRecord(): BelongsTo
    {
        return $this->belongsTo(DataGridRecord::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
