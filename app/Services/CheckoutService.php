<?php
namespace App\Services;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Exception;
class CheckoutService
{
    public function checkout($user, $productId, $quantity)
    {
        DB::beginTransaction();

        try {
            $product = Product::lockForUpdate()->findOrFail($productId);

            if ($product->stock < $quantity) {
                throw new Exception('Not enough stock available.');
            }

            // Kurangi stok produk
            $product->stock -= $quantity;
            $product->save();

            // Buat pesanan
            $order = new Order();
            $order->user_id = $user->id;
            $order->total_amount = $product->price * $quantity;
            $order->status = 'pending';
            $order->save();

            // Buat item pesanan
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $product->id;
            $orderItem->quantity = $quantity;
            $orderItem->price = $product->price;
            $orderItem->save();

            DB::commit();

            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
