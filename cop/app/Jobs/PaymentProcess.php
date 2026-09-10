<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private readonly array $payload) {}

    public function handle(): void
    {
        Payment::create($this->payload);
        $this->sendPaymentRequest($this->payload);
    }

     private function transaction(array $payload){
        try {
          Transaction::create($payload);
            } catch (\Throwable $e) {
            Log::error('Transaction failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    private function sendPaymentRequest(array $payload){

     //send payment request to payment service provider: safaricom mpesa(daraja api), 
        $this->transaction($payload); // will await request sent to service provider before updating fields like status. this what Payment model will use to know whether the transction succeded or not.
    }
    public function failed(\Throwable $exception): void
    {
        Log::error('payment failed', [
            'payload' => $this->payload,
            'error'   => $exception->getMessage(),
        ]);
    }
}
