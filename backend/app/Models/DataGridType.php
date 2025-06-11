<?php
// app/Models/DataGridType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $data_grid_id
 * @property bool $is_global
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\DataGrid|null $dataGrid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DataGridRecord> $dataGridRecords
 * @property-read int|null $data_grid_records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType forDataGrid(string $dataGridId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType local()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereDataGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereIsGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataGridType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DataGridType extends Model
{
    use HasFactory;

    protected $table = 'data_grid_types';

    protected $fillable = [
        'name',
        'data_grid_id',
        'is_global',
        'created_by'
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public static function getAvailableTypes(string $dataGridId, string $search = null)
    {
        $query = self::where(function ($q) use ($dataGridId) {
            $q->where('data_grid_id', $dataGridId)
                ->orWhere('is_global', true);
        });

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('is_global', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function dataGrid(): BelongsTo
    {
        return $this->belongsTo(DataGrid::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dataGridRecords(): HasMany
    {
        return $this->hasMany(DataGridRecord::class, 'type_id');
    }

    public function scopeForDataGrid($query, string $dataGridId)
    {
        return $query->where(function ($q) use ($dataGridId) {
            $q->where('data_grid_id', $dataGridId)
                ->orWhere('is_global', true);
        });
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeLocal($query)
    {
        return $query->where('is_global', false);
    }
}
