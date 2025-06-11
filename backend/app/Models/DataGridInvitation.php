<?php
// app/Models/DataGridInvitation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property int $id
 * @property string $data_grid_id
 * @property int $invited_by
 * @property int $user_id
 * @property string $token
 * @property array<array-key, mixed> $permissions
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DataGrid $dataGrid
 * @property-read \App\Models\User $invitedBy
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereDataGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridInvitation whereUserId($value)
 * @mixin \Eloquent
 */
class DataGridInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_grid_id',
        'invited_by',
        'user_id',
        'token',
        'permissions',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected $with = ['dataGrid', 'invitedBy'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
        });
    }

    public function dataGrid(): BelongsTo
    {
        return $this->belongsTo(DataGrid::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }
}
