<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VivaGateway
{
    public function createPaymentOrder(Order $order): array
    {
        $this->ensureConfigured();

        $payload = [
            'amount' => $order->total,
            'sourceCode' => (string) config('services.viva.source_code'),
            'merchantTrns' => $order->code,
            'customerTrns' => 'Order '.$order->code,
            'paymentTimeout' => 1800,
            'customer' => array_filter([
                'email' => $order->customer_email,
                'fullName' => $order->customer_name,
                'phone' => $order->customer_phone,
                'countryCode' => config('services.viva.country_code', 'GR'),
                'requestLang' => config('services.viva.request_lang', 'el-GR'),
            ], fn (mixed $value): bool => filled($value)),
        ];

        $response = $this->api()
            ->post($this->apiBaseUrl().'/checkout/v2/orders', $payload)
            ->throw();

        $responseBody = $response->json();

        if (empty($responseBody['orderCode'])) {
            throw new RuntimeException('Viva did not return a payment order code.');
        }

        $orderCode = (string) $responseBody['orderCode'];

        return [
            'order_code' => $orderCode,
            'checkout_url' => $this->checkoutUrl($orderCode),
            'payload' => $responseBody + [
                'request' => $this->safeCreateOrderPayload($payload),
                'x_viva_correlation_id' => $response->header('X-Viva-CorrelationId') ?: $response->header('X-Viva-Correlationid'),
            ],
        ];
    }

    public function retrieveTransaction(string $transactionId): array
    {
        $this->ensureConfigured();

        return $this->api()
            ->get($this->apiBaseUrl().'/checkout/v2/transactions/'.$transactionId)
            ->throw()
            ->json();
    }

    public function webhookVerificationKey(): array
    {
        if (filled(config('services.viva.webhook_verification_key'))) {
            return ['key' => (string) config('services.viva.webhook_verification_key')];
        }

        foreach (['merchant_id', 'api_key'] as $key) {
            if (blank(config('services.viva.'.$key))) {
                throw new RuntimeException('Missing Viva webhook configuration: '.$key);
            }
        }

        try {
            $response = Http::withBasicAuth(config('services.viva.merchant_id'), config('services.viva.api_key'))
                ->acceptJson()
                ->timeout(20)
                ->get($this->checkoutBaseUrl().'/api/messages/config/token')
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 'unknown';

            throw new RuntimeException(
                "Viva webhook verification key request failed with HTTP {$status}. Check VIVA_ENV, VIVA_MERCHANT_ID and VIVA_API_KEY belong to the same demo/live account.",
                0,
                $exception,
            );
        }

        if (empty($response['Key'])) {
            throw new RuntimeException('Viva did not return a webhook verification key.');
        }

        return ['key' => $response['Key']];
    }

    private function api(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('services.viva.client_id'), config('services.viva.client_secret'))
            ->timeout(20)
            ->post($this->accountsBaseUrl().'/connect/token', [
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json();

        if (empty($response['access_token'])) {
            throw new RuntimeException('Viva did not return an access token.');
        }

        return $response['access_token'];
    }

    private function checkoutUrl(string $orderCode): string
    {
        return $this->checkoutBaseUrl().'/web/checkout?ref='.urlencode($orderCode);
    }

    private function safeCreateOrderPayload(array $payload): array
    {
        return [
            'amount' => $payload['amount'] ?? null,
            'sourceCode' => $payload['sourceCode'] ?? null,
            'merchantTrns' => $payload['merchantTrns'] ?? null,
            'paymentTimeout' => $payload['paymentTimeout'] ?? null,
            'customer' => [
                'has_email' => filled($payload['customer']['email'] ?? null),
                'has_full_name' => filled($payload['customer']['fullName'] ?? null),
                'has_phone' => filled($payload['customer']['phone'] ?? null),
                'countryCode' => $payload['customer']['countryCode'] ?? null,
                'requestLang' => $payload['customer']['requestLang'] ?? null,
            ],
        ];
    }

    private function ensureConfigured(): void
    {
        foreach (['client_id', 'client_secret', 'source_code'] as $key) {
            if (blank(config('services.viva.'.$key))) {
                throw new RuntimeException('Missing Viva configuration: '.$key);
            }
        }
    }

    private function apiBaseUrl(): string
    {
        return config('services.viva.environment') === 'production'
            ? 'https://api.vivapayments.com'
            : 'https://demo-api.vivapayments.com';
    }

    private function accountsBaseUrl(): string
    {
        return config('services.viva.environment') === 'production'
            ? 'https://accounts.vivapayments.com'
            : 'https://demo-accounts.vivapayments.com';
    }

    private function checkoutBaseUrl(): string
    {
        return config('services.viva.environment') === 'production'
            ? 'https://www.vivapayments.com'
            : 'https://demo.vivapayments.com';
    }
}
