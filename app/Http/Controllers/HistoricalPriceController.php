<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HistoricalPriceController extends Controller
{
    public function index(Request $request, $productId)
    {
        $product = Product::with(['historicalPrices' => function($query) {
            $query->orderBy('price_date', 'desc');
        }])->findOrFail($productId);

        return view('historical_prices.index', compact('product'));
    }
}
