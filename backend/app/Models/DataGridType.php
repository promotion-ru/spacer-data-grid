<?php
// app/Models/DataGridType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
