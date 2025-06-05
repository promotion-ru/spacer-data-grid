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
 * @property-read DataGridRecord $dataGridRecord
 * @property-read Media $media
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
