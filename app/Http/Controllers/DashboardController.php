<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Today's metrics
        $today         = Carbon::today()->toDateString();
        $todaySales    = Sale::whereDate('sale_date', $today)->sum('total_amount');
        $todayOrders   = Sale::whereDate('sale_date', $today)->count();

        // This week's metrics
        $startOfWeek   = Carbon::today()->startOfWeek()->toDateString();
        $weekSales     = Sale::whereBetween('sale_date', [$startOfWeek, $today])->sum('total_amount');
        $weekOrders    = Sale::whereBetween('sale_date', [$startOfWeek, $today])->count();

        // Low stock items
        $lowStockItems = Product::whereColumn('quantity', '<', 'min_stock_alert')
            ->orderBy('quantity')
            ->get(['name','quantity']);

        // Top-selling product of last 7 days
        $topProduct = Sale::selectRaw('product_id, SUM(total_amount) as revenue')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.sale_date', '>=', Carbon::today()->subDays(7))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->first();

        return view('dashboard', compact(
            'todaySales','todayOrders',
            'weekSales','weekOrders',
            'lowStockItems','topProduct'
        ));
    }
}