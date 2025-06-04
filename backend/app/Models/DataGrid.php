<?php
// app/Models/DataGrid.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DataGrid extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'image_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $with = ['media'];
    protected $appends = ['image_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(DataGridRecord::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('data_grid_image')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
                'application/octet-stream'
            ]);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('grid_image');
    }
}
