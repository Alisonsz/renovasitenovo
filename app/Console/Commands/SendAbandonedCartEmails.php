<?php

namespace App\Console\Commands;

use App\Services\Store\CartRecoveryService;
use Illuminate\Console\Command;

class SendAbandonedCartEmails extends Command
{
    protected $signature = 'cart:send-recovery {--limit= : Max carts to process this run}';

    protected $description = 'Detect abandoned carts and queue recovery emails with a discount coupon.';

    public function handle(CartRecoveryService $recovery): int
    {
        if (! config('cart.recovery_enabled')) {
            $this->info('Cart recovery is disabled (cart.recovery_enabled=false).');

            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('cart.recovery_batch_size', 50));
        $carts = $recovery->findAbandonable($limit);

        if ($carts->isEmpty()) {
            $this->info('No abandoned carts to recover.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($carts as $cart) {
            try {
                if ($recovery->sendRecovery($cart)) {
                    $sent++;
                    $this->line("Queued recovery for cart #{$cart->id} ({$cart->email}).");
                }
            } catch (\Throwable $e) {
                $this->error("Cart #{$cart->id}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Done. Queued {$sent} recovery email(s).");

        return self::SUCCESS;
    }
}
