<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function create()
    {
        // get products & their current stock + price
        $products = Product::select('id','name','quantity','price','unit')->get();
        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        // 1) Validate incoming rows (ignore blank lines)
        $data = $request->validate([
            'sale_date'              => 'required|date',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required_with:items.*.qty|exists:products,id',
            'items.*.qty'            => 'required_with:items.*.product_id|integer|min:1',
        ], [
            'items.required'                      => 'You must add at least one line item.',
            'items.*.product_id.required_with'    => 'Please select a product whenever you enter a quantity.',
            'items.*.product_id.exists'           => 'Selected product does not exist.',
            'items.*.qty.required_with'           => 'Please enter a quantity whenever you select a product.',
            'items.*.qty.integer'                 => 'Quantity must be a whole number.',
            'items.*.qty.min'                     => 'Quantity must be at least 1.',
        ]);

        // 2) Clean out any completely blank rows
        $cleanItems = collect($data['items'])
            ->filter(fn($it) => !empty($it['product_id']) && !empty($it['qty']))
            ->values()
            ->all();

        // 3) Wrap in a transaction and capture $sale by reference
        $sale = null;
        DB::transaction(function() use ($cleanItems, $data, &$sale) {
            // create the sale header
            $sale = Sale::create([
                'sale_date'    => $data['sale_date'],
                'total_amount' => 0,
                'created_by'   => auth()->id(),
            ]);

            $total = 0;
            foreach ($cleanItems as $it) {
                $product = Product::findOrFail($it['product_id']);

                // stock check → JSON error if fails
                if ($it['qty'] > $product->quantity) {
                    throw new HttpResponseException(
                        response()->json(
                            ['error' => "Not enough stock for {$product->name}"],
                            400
                        )
                    );
                }

                $lineTotal = $product->price * $it['qty'];
                $total    += $lineTotal;

                // decrement stock
//                $product->decrement('quantity', $it['qty']);
                $product->quantity -= $it['qty'];
                $product->save();
                // create each line‐item
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'qty'        => $it['qty'],
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);
            }

            // update the final total on the header
            $sale->update(['total_amount' => $total]);
        });

        // 4) Return JSON if requested (AJAX), otherwise redirect
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sale recorded successfully.',
                'id'      => $sale->id,
            ], 200);
        }

        return redirect()
            ->route('sales.index')
            ->with('success','Sale recorded successfully.');
    }

    public function index(Request $request)
    {
        // optional filter by date range or product
        $query = Sale::withCount('items')->orderBy('sale_date','desc');

        if ($request->filled('from')) {
            $query->whereDate('sale_date','>=',$request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date','<=',$request->to);
        }

        $sales = $query->paginate(15)->withQueryString();
        return view('sales.index', compact('sales'));
    }


    public function show(Sale $sale)
    {
        $sale->load('items.product');
        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        // eager load items & product
        $sale->load('items.product');

        // pass to a Blade view for styling
        $pdf = PDF::loadView('sales.receipt', compact('sale'))
            ->setPaper('a4', 'portrait');

        // either download:
        return $pdf->download("receipt-{$sale->id}.pdf");

        // or stream inline:
        // return $pdf->stream("receipt-{$sale->id}.pdf");
    }
}
