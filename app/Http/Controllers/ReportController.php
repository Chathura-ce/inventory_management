<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function salesSummary(Request $request)
    {
        $from  = $request->input('from',  Carbon::today()->subDays(30)->toDateString());
        $to    = $request->input('to',    Carbon::today()->toDateString());
        $group = $request->input('group','day'); // day|week|month

        $summary = Sale::query()
            ->select([
                DB::raw($this->groupSelect($group) . ' as period'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('IF(COUNT(*)>0, SUM(total_amount)/COUNT(*),0) as avg_order_value'),
            ])
            ->whereBetween('sale_date', [$from, $to])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // if JSON requested:
        if ($request->wantsJson()) {
            return response()->json([
                'from'   => $from,
                'to'     => $to,
                'group'  => $group,
                'summary'=> $summary,
            ]);
        }

        // CSV export
        if ($request->query('export') === 'csv') {
            return $this->streamCsv(
                "sales_summary_{$from}_{$to}.csv",
                ['Period','Orders','Revenue','Avg Order Value'],
                $summary->toArray()
            );
        }

        // browser‐first‐load: just show the page
        return view('reports.index');
    }

    public function stockBalance(Request $request)
    {
        $asOf = $request->input('as_of', Carbon::today()->toDateString());
        $prods = Product::select('name','unit','quantity','price')->orderBy('name')->get();
        $balance = $prods->map(fn($p) => [
            'name'        => $p->name,
            'unit'        => $p->unit,
            'on_hand'     => $p->quantity,
            'unit_cost'   => $p->price,
            'total_value' => $p->quantity * $p->price,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'as_of'    => $asOf,
                'products' => $balance,
            ]);
        }

        if ($request->query('export') === 'csv') {
            return $this->streamCsv(
                "stock_balance_{$asOf}.csv",
                ['Product','Unit','On-Hand Qty','Unit Cost','Total Value'],
                $balance->toArray()
            );
        }

        return view('reports.index');
    }

    public function stockMovement(Request $request)
    {
        $from    = $request->input('from', Carbon::today()->subDays(30)->toDateString());
        $to      = $request->input('to',   Carbon::today()->toDateString());
        $product = $request->input('product');
        $type    = $request->input('type');

        // incoming
        $in = DB::table('stock_entries')
            ->select('created_at as date','product_id', DB::raw("'purchase' as type"), 'quantity as qty');

        // outgoing
        $out = DB::table('sale_items')
            ->join('sales','sales.id','=','sale_items.sale_id')
            ->select('sales.sale_date as date','sale_items.product_id',
                DB::raw("'sale' as type"), DB::raw('sale_items.qty * -1 as qty'));

        $rows = $in->unionAll($out)
            ->orderBy('date')
            ->get()
            ->filter(fn($r) =>
                (!$product || $r->product_id==$product) &&
                ($r->date >= $from) &&
                ($r->date <= $to) &&
                (!$type || $r->type==$type)
            )
            ->sortBy('date')
            ->values();

        $balance = 0;
        $movements = $rows->map(function($r) use(&$balance) {
            $balance += $r->qty;
            return [
                'date'          => Carbon::parse($r->date)->toDateString(),
                'product'       => optional(Product::find($r->product_id))->name ?: 'Unknown',
                'type'          => ucfirst($r->type),
                'qty'           => abs($r->qty),
                'balance_after' => $balance,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'from'      => $from,
                'to'        => $to,
                'product'   => $product,
                'type'      => $type,
                'movements' => $movements,
            ]);
        }

        if ($request->query('export') === 'csv') {
            return $this->streamCsv(
                "stock_movement_{$from}_{$to}.csv",
                ['Date','Product','Type','Qty','Balance After'],
                $movements->toArray()
            );
        }

        return view('reports.index');
    }

    protected function groupSelect(string $g): string
    {
        return match($g) {
            'week'  => "DATE_FORMAT(sale_date,'%x-%v')",
            'month' => "DATE_FORMAT(sale_date,'%Y-%m')",
            default => 'DATE(sale_date)',
        };
    }

    protected function streamCsv(string $fn, array $cols, array $rows): StreamedResponse
    {
        $cb = function() use($cols,$rows) {
            $h = fopen('php://output','w');
            fputcsv($h,$cols);
            foreach($rows as $r) fputcsv($h, array_values((array)$r));
            fclose($h);
        };
        return response()->streamDownload($cb, $fn, ['Content-Type'=>'text/csv']);
    }
}
