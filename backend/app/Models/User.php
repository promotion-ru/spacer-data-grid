<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $surname
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $avatar_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Media|null $avatar
 * @property-read mixed $avatar_url
 * @property-read Collection<int, \App\Models\DataGrid> $dataGrids
 * @property-read int|null $data_grids_count
 * @property-read Collection<int, \App\Models\DataGridInvitation> $gridInvitations
 * @property-read int|null $grid_invitations_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, \App\Models\DataGridInvitation> $pendingInvitations
 * @property-read int|null $pending_invitations_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, \App\Models\DataGrid> $sharedGrids
 * @property-read int|null $shared_grids_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'avatar_id',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $appends = ['avatar_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_id', 'id');
    }

    public function dataGrids(): HasMany
    {
        return $this->hasMany(DataGrid::class);
    }

    public function sharedGrids(): BelongsToMany
    {
        return $this->belongsToMany(DataGrid::class, 'data_grid_members')
            ->withPivot(['permissions', 'invited_by'])
            ->withTimestamps();
    }

    public function pendingInvitations(): HasMany
    {
        return $this->gridInvitations()->pending();
    }

    public function gridInvitations(): HasMany
    {
        return $this->hasMany(DataGridInvitation::class, 'user_id', 'id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        switch ($eventName) {
            case 'created':
                $activity->description = "Создан новый пользователь: {$this->name} (ID: {$this->id})";
                break;
            case 'updated':
                $changes = $this->getActivityLogChanges();
                if (empty($changes)) {
                    $activity->description = "Обновлены данные пользователя: {$this->name}";
                } else {
                    $activity->description = implode(', ', $changes);
                }
                break;
            case 'deleted':
                $activity->description = "Удален пользователь: {$this->name} (ID: {$this->id})";
                break;
        }
    }

    private function getActivityLogChanges(): array
    {
        $changes = [];
        $dirty = $this->getDirty();

        foreach ($dirty as $field => $newValue) {
            if (!in_array($field, $this->getActivitylogOptions()->logAttributes)) {
                continue;
            }
            $oldValue = $this->getOriginal($field);
            $fieldName = match ($field) {
                'name' => 'Имя',
                'surname' => 'Фамилия',
                'email' => 'Email',
                'avatar_id' => 'Аватар',
                default => ucfirst($field)
            };
            $changes[] = "{$fieldName} с '{$oldValue}' на '{$newValue}'";
        }

        return $changes;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'surname', 'avatar_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'active'            => 'boolean',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getFirstMediaUrl('avatars') ?: null,
        );
    }

}
