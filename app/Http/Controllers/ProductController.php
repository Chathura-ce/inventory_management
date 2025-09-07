<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HistoricalPrice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'min_stock_alert' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048'
        ], [
            'category_id.required' => 'Please select a category.',
            'sku.unique' => 'This SKU is already in use.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($validated);
        // log into historical_prices
        HistoricalPrice::create([
            'product_id' => $product->id,
            'price_date' => now()->toDateString(),
            'narahenpita_retail' => $validated['price'],
        ]);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:20',
            'min_stock_alert' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'predict' => 'required|boolean',
        ], [
            'category_id.required' => 'Please select a category.',
            'sku.unique' => 'This SKU is already in use.',
            'predict.required' => 'Predict is required',
        ]);

        // 🚨 Business validation for predict
        if ($request->predict == 1) {
            $historyCount = \App\Models\HistoricalPrice::where('product_id', $product->id)->count();

            if ($historyCount < 730) {
                return back()
                    ->withErrors(['predict' => 'Prediction can only be enabled if the product has at least 730 days of historical prices.'])
                    ->withInput();
            }
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        if ($validated['quantity'] != $product->quantity) {
            $difference = $validated['quantity'] - $product->quantity;

            DB::table('stock_entries')->insert([
                'product_id' => $product->id,
                'quantity'   => $difference,
                'source'       => $difference > 0 ? 'adjustment_in' : 'adjustment_out',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // before $product->update($validated);
        if ($validated['price'] != $product->price) {
            HistoricalPrice::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'price_date' => now()->toDateString(),
                ],
                [
                    'narahenpita_retail' => $validated['price'],
                    'currency' => 'LKR',
                ]
            );
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}

