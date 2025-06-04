<?php
// app/Models/GridRecord.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DataGridRecord extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'data_grid_id',
        'created_by',
        'name',
        'operation_type_id',
        'type_id',
        'description',
        'amount',
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

    public function dataGridRecordMedia(): HasMany
    {
        return $this->hasMany(DataGridRecordMedia::class);
    }

    public function attachments(): BelongsToMany
    {
        return $this->media()->wherePivot('media_type', 'attachment');
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
}
