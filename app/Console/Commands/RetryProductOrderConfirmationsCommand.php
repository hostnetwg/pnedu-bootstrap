<?php

namespace App\Console\Commands;

use App\Mail\ProductOrderConfirmationMail;
use App\Models\FormOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RetryProductOrderConfirmationsCommand extends Command
{
    protected $signature = 'legal:retry-product-confirmations {--limit=50}';

    protected $description = 'Ponów wysyłkę potwierdzeń zamówień kursów, które nie zostały przekazane do poczty';

    public function handle(): int
    {
        $orders = FormOrder::query()
            ->where('order_kind', 'product')
            ->whereNull('legal_confirmation_sent_at')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                Mail::to($order->orderer_email)->send(new ProductOrderConfirmationMail($order));
                $order->update([
                    'legal_confirmation_sent_at' => now('UTC'),
                    'legal_confirmation_failed_at' => null,
                    'legal_confirmation_error' => null,
                ]);
                $sent++;
            } catch (Throwable $exception) {
                $failed++;
                $order->update([
                    'legal_confirmation_failed_at' => now('UTC'),
                    'legal_confirmation_error' => mb_substr($exception->getMessage(), 0, 1000),
                ]);
                Log::error('Product confirmation retry failed', [
                    'form_order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Wysłano ponownie: {$sent}. Błędy: {$failed}.");

        return self::SUCCESS;
    }
}
