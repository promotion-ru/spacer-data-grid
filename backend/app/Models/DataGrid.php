<?php
// app/Models/DataGrid.php
namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
        return $this->getFirstMediaUrl('data_grid_image');
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(DataGridInvitation::class);
    }

    public function hasAccess(User $user): bool
    {
        return $this->isOwner($user) || $this->isMember($user);
    }

    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function members(): HasMany
    {
        return $this->hasMany(DataGridMember::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DataGridType::class, 'type_id');
    }

    public function canUserPerform(User $user, string $action): bool
    {
        $permissions = $this->getUserPermissions($user);
        return in_array($action, $permissions);
    }

    public function getUserPermissions(User $user): array
    {
        if ($this->isOwner($user)) {
            return ['view', 'create', 'update', 'delete', 'manage'];
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member ? $member->permissions : [];
    }

    public function logAction(
        string  $action,
        string  $description,
        ?string $targetUserId = null,
        array   $oldValues = [],
        array   $newValues = [],
        array   $metadata = []
    ): DataGridLog
    {
        return $this->logs()->create([
            'action'         => $action,
            'user_id'        => Auth::id(),
            'target_user_id' => $targetUserId,
            'description'    => $description,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'metadata'       => $metadata,
        ]);
    }

    public function logs()
    {
        return $this->hasMany(DataGridLog::class, 'data_grid_id');
    }

    public function logRecordAction(
        string $action,
        string $description,
        ?int   $recordId = null,
        array  $oldValues = [],
        array  $newValues = [],
        array  $metadata = []
    ): DataGridRecordLog
    {
        return $this->recordLogs()->create([
            'data_grid_record_id' => $recordId,
            'action'              => $action,
            'user_id'             => Auth::id(),
            'description'         => $description,
            'old_values'          => $oldValues,
            'new_values'          => $newValues,
            'metadata'            => $metadata,
        ]);
    }

    public function recordLogs()
    {
        return $this->hasMany(DataGridRecordLog::class, 'data_grid_id');
    }
}
