<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $currency;
    protected string $returnBaseUrl;

    public function __construct()
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId = (string) config('services.paypal.client_id', '');
        $this->clientSecret = (string) config('services.paypal.client_secret', '');
        $this->currency = (string) config('services.paypal.currency', 'USD');
        $this->returnBaseUrl = rtrim((string) config('services.paypal.return_base_url', 'http://127.0.0.1:8000'), '/');
    }

    /**
     * Obtener el Access Token OAuth2 desde PayPal
     */
    public function getAccessToken(): string
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Las credenciales de PayPal (Client ID o Secret) no están configuradas en el entorno.');
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new Exception('No se pudo autenticar con PayPal API: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Crear orden de pago en PayPal
     *
     * @param int|string $orderId
     * @param float $amount
     * @return array [ 'order_id' => string, 'approval_url' => string, 'raw' => array ]
     */
    public function createOrder(int|string $orderId, float $amount): array
    {
        $accessToken = $this->getAccessToken();

        $successUrl = "{$this->returnBaseUrl}/api/v1/payments/paypal/success";
        $cancelUrl = "{$this->returnBaseUrl}/api/v1/payments/paypal/cancel";

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $orderId,
                    'amount' => [
                        'currency_code' => $this->currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    'description' => "Compra en Ecommerce - Orden #{$orderId}",
                ],
            ],
            'application_context' => [
                'brand_name' => config('app.name', 'Ecommerce API'),
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders", $payload);

        if (! $response->successful()) {
            throw new Exception('Error al crear orden en PayPal: ' . $response->body());
        }

        $data = $response->json();
        $paypalOrderId = $data['id'] ?? null;

        // Extraer approval url
        $approvalUrl = null;
        if (isset($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
        }

        return [
            'paypal_order_id' => $paypalOrderId,
            'approval_url' => $approvalUrl,
            'raw' => $data,
        ];
    }

    /**
     * Capturar el pago de una orden previamente aprobada en PayPal
     *
     * @param string $paypalOrderId
     * @return array
     */
    public function capturePayment(string $paypalOrderId): array
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture", (object)[]);

        if (! $response->successful()) {
            throw new Exception('Error al capturar pago en PayPal: ' . $response->body());
        }

        return $response->json();
    }
}
