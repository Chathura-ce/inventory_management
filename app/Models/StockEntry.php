<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockEntry extends Model
{
    protected $fillable = ['product_id', 'quantity', 'source'];

    /**
     * Enable timestamps (created_at, updated_at).
     */
    public $timestamps = true;

    /**
     * Each stock entry belongs to a product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
