<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class NewProductLaunch extends Notification
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
            'type'        => 'new_product',
            'product_id'  => $this->product->id,
            'product_name'=> $this->product->name,
            'message'     => "New product launched: {$this->product->name}.",
        ];
    }
}