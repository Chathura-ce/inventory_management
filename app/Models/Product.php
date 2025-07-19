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

}
