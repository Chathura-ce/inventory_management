<?php
namespace App\Observers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LowStockAlert;
use App\Notifications\NewProductLaunch;

class ProductObserver
{
    public function created(Product $product)
    {
        // Notify all admin users about new product
//        $admins = User::where('role', 'admin')->get();
        $admins = User::all();
        Notification::send($admins, new NewProductLaunch($product));

        if ($product->quantity < $product->min_stock_alert) {
            Notification::send(User::all(), new LowStockAlert($product));
        }
    }


    public function updated(Product $product)
    {
        // Only fire low-stock once when quantity dips below reorder_level
        $origQty = $product->getOriginal('quantity');
        if ($product->quantity < $product->min_stock_alert
            && $origQty >= $product->min_stock_alert) {
//            $admins = User::where('role', 'admin')->get();
            $admins = User::all();
            Notification::send($admins, new LowStockAlert($product));
        }
    }
}