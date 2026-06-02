<?php

namespace App\Services\Clinic;

use App\Models\Order;
use App\Models\Product;

class TreatmentProvisioner
{
    /**
     * When an order is paid, create a Treatment for each treatment-product line
     * so the clinic can start scheduling sessions. Idempotent: keyed by
     * (order_id, order_item_id) so re-firing the paid event won't duplicate.
     */
    public function provisionFromOrder(Order $order): int
    {
        $order->loadMissing(['items', 'customer']);

        if (! $order->customer) {
            return 0;
        }

        $created = 0;

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::query()->find($item->product_id);
            if (! $product || ! $product->is_treatment) {
                continue;
            }

            $sessions = (int) ($product->sessions_count ?: 1);

            // One treatment per unit purchased (e.g. buying 2 packages → 2 treatments).
            for ($unit = 0; $unit < $item->quantity; $unit++) {
                $exists = $order->customer->treatments()
                    ->where('order_id', $order->id)
                    ->where('order_item_id', $item->id)
                    ->count();

                // Already provisioned the right number for this line? stop.
                if ($exists >= $item->quantity) {
                    break;
                }

                $order->customer->treatments()->create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'name' => $product->name,
                    'total_sessions' => $sessions,
                    'session_duration_min' => $product->session_duration_min ?: 30,
                    'status' => 'active',
                ]);
                $created++;
            }
        }

        return $created;
    }
}
