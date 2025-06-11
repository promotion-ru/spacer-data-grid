<?php
// app/Models/DataGridMember.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property string $data_grid_id
 * @property int $user_id
 * @property int $invited_by
 * @property array<array-key, mixed> $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DataGrid $dataGrid
 * @property-read \App\Models\User $invitedBy
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereDataGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridMember whereUserId($value)
 * @mixin \Eloquent
 */
class DataGridMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_grid_id',
        'user_id',
        'invited_by',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected $with = ['user', 'invitedBy'];

    public function dataGrid(): BelongsTo
    {
        return $this->belongsTo(DataGrid::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }
}
