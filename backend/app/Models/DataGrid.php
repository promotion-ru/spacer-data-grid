<?php
// app/Models/DataGrid.php
namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 *
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $image_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $image_url
 * @property-read MediaCollection<int, Media> $media
 * @property-read Collection<int, DataGridRecord> $records
 * @property-read User $user
 */
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
