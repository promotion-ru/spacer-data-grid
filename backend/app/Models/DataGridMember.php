<?php
// app/Models/DataGridMember.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
