<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'product_stocks';

    protected $fillable = [
        'product_id',
        'size',
        'quantity',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_quantity' => 'integer',
        ];
    }

    public static function normalizeSize(string $size): string
    {
        return strtoupper(trim($size));
    }

    /**
     * ok = acima do mínimo | low = no mínimo ou abaixo | out = zerado
     */
    public function getStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out';
        }

        return $this->quantity <= $this->min_quantity ? 'low' : 'ok';
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'out' => $query->where('quantity', '<=', 0),
            'low' => $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity'),
            'ok' => $query->whereColumn('quantity', '>', 'min_quantity'),
            default => $query,
        };
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
