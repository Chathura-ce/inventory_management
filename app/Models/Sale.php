<?php

// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['sale_date', 'total_amount', 'created_by'];

    protected $casts = [
        'sale_date' => 'date',      // will become a Carbon instance
        'created_at'=> 'datetime',  // optional
        'updated_at'=> 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
