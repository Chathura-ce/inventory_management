<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Product;

class LowStockAlert extends Notification
{
    use Queueable;

    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type'        => 'low_stock',
            'product_id'  => $this->product->id,
            'product_name'=> $this->product->name,
            'quantity'    => $this->product->quantity,
            'message'     => "Low stock: {$this->product->name} has only {$this->product->quantity} left.",
        ];
    }
}