<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class StockController extends Controller
{
    public function create()
    {
        $products = \App\Models\Product::select('id', 'sku', 'name','price','unit')->get();
        return view('stocks.create', compact('products'));
    }

    public function store(Request $request)
    {
        if ($request->expectsJson()) {
            try {
                $data = $request->validate([
                    'items' => 'required|array',
                    'items.*.sku' => 'required|string|exists:products,sku',
                    'items.*.quantity' => 'required|integer|min:1',
                    'items.*.price' => 'nullable|numeric|min:0',
                    'items.*.source' => 'nullable|string|in:manual,import',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
        } else {
            $data = $request->validate([
                'items' => 'required|array',
                'items.*.sku' => 'required|string|exists:products,sku',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'nullable|numeric|min:0',
                'items.*.source' => 'nullable|string|in:manual,import',
            ]);
        }

        foreach ($data['items'] as $item) {
            $product = Product::where('sku', $item['sku'])->first();
            if ($product) {
                $product->increment('quantity', $item['quantity']);

                if (isset($item['price'])) {
                    $product->price = $item['price'];
                    $product->save();

                    // ➕ Create stock entry
                    StockEntry::create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'source' => $item['source'] // default to 'manual'
                    ]);
                }

            }
        }

        return response()->json(['message' => 'Stock updated successfully.']);
    }

}
