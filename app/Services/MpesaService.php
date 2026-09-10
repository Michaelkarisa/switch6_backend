<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin wrapper around Safaricom's Daraja STK Push API.
 *
 * When services.mpesa.enabled is false (default, e.g. local/dev without
 * sandbox credentials), calls run in "simulate" mode: nothing is sent over
 * the network, a synthetic reference is returned, and the flow can still be
 * exercised end-to-end through the transaction/callback machinery.
 */
class MpesaService
{
    /**
     * Initiate an STK push for the given payment. Returns whether the
     * *request* was accepted by Daraja — NOT whether the customer has paid.
     * Final payment status only arrives later via the callback URL.
     */
    public function stkPush(Payment $payment): array
    {
        if (! config('services.mpesa.enabled')) {
            return $this->simulate($payment);
        }

        try {
            $token = $this->accessToken();

            if (! $token) {
                return ['ok' => false, 'reference' => null, 'raw' => ['error' => 'Could not obtain M-Pesa access token']];
            }

            $timestamp = now()->format('YmdHis');
            $shortcode = (string) config('services.mpesa.shortcode');
            $password = base64_encode($shortcode.config('services.mpesa.passkey').$timestamp);
            $phone = $this->normalizePhone($payment->phone);

            $response = Http::withToken($token)
                ->timeout(20)
                ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $shortcode,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'TransactionType'   => 'CustomerPayBillOnline',
                    'Amount'            => (int) $payment->amount,
                    'PartyA'            => $phone,
                    'PartyB'            => $shortcode,
                    'PhoneNumber'       => $phone,
                    'CallBackURL'       => config('services.mpesa.callback_url'),
                    'AccountReference'  => $payment->id,
                    'TransactionDesc'   => ucfirst($payment->type).' payment',
                ]);

            $data = $response->json() ?? [];

            return [
                'ok'        => $response->successful() && (string) ($data['ResponseCode'] ?? null) === '0',
                'reference' => $data['CheckoutRequestID'] ?? null,
                'raw'       => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('mpesa stk push failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return ['ok' => false, 'reference' => null, 'raw' => ['error' => $e->getMessage()]];
        }
    }

    private function accessToken(): ?string
    {
        return Cache::remember('mpesa_access_token', 55 * 60, function () {
            $response = Http::withBasicAuth(
                config('services.mpesa.consumer_key'),
                config('services.mpesa.consumer_secret')
            )->timeout(15)->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function baseUrl(): string
    {
        return config('services.mpesa.env') === 'production'
            ? config('services.mpesa.production_base_url')
            : config('services.mpesa.sandbox_base_url');
    }

    /** Daraja expects 2547XXXXXXXX / 2541XXXXXXXX, no leading 0 or +. */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254'.$digits;
        }

        return $digits;
    }

    private function simulate(Payment $payment): array
    {
        Log::info('mpesa simulate mode (services.mpesa.enabled=false): STK push not actually sent', [
            'payment_id' => $payment->id,
        ]);

        return [
            'ok'        => true,
            'reference' => 'SIM-'.Str::uuid(),
            'raw'       => ['simulated' => true],
        ];
    }
}
