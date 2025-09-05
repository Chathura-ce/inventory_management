<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'quantity',
        'price',
        'description',
        'unit',
        'min_stock_alert',
        'max_stock',
        'image_path',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function adjustStock(int $deltaQty, string $source): void
    {
        // 1) Ledger row
        \App\Models\StockEntry::create([
            'product_id' => $this->id,
            'quantity'   => $deltaQty,   // may be negative
            'source'     => $source,
        ]);

        // 2) Update on-hand stock
        $this->increment('quantity', $deltaQty);
    }

}
