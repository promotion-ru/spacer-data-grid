<?php
// app/Models/RecordMedia.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
