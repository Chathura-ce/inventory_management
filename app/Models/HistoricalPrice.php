<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\HistoricalPrice
 *
 * @property int $id
 * @property int $product_id
 * @property Carbon $price_date
 * @property float|null $pettah_wholesale
 * @property float|null $narahenpita_retail
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static betweenDates($start, $end)
 */
class HistoricalPrice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'historical_prices';

    /**
     * Mass‑assignable attributes.
     */
    protected $fillable = [
        'product_id',
        'price_date',
        'pettah_wholesale',
        'narahenpita_retail',
        'currency',
    ];

    /**
     * Attribute type casting.
     */
    protected $casts = [
        'price_date'         => 'date:Y-m-d',
        'pettah_wholesale'   => 'decimal:2',
        'narahenpita_retail' => 'decimal:2',
    ];

    /* -------------------------------------------------------------------- */
    /*  Relationships                                                       */
    /* -------------------------------------------------------------------- */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /* -------------------------------------------------------------------- */
    /*  Query Scopes                                                        */
    /* -------------------------------------------------------------------- */

    /**
     * Scope: constrain to a date range.
     */
    public function scopeBetweenDates($query, string|Carbon $start, string|Carbon $end)
    {
        return $query->whereBetween('price_date', [Carbon::parse($start), Carbon::parse($end)]);
    }
}
