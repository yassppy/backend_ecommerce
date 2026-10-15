<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class PayPalPaymentController extends Controller
{
    public function __construct(
        protected PayPalService $payPalService
    ) {}

    #[OA\Post(
        path: "/api/v1/payments/paypal/create/{order_id}",
        summary: "Crear orden de pago en PayPal",
        description: "Genera una orden de pago en PayPal Sandbox para una orden en estado pending del usuario. Retorna la approval_url para que el frontend redirija al cliente a pagar en PayPal.",
        tags: ["Pagos PayPal"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "order_id",
                in: "path",
                description: "ID de la orden pendiente a pagar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Orden PayPal creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Orden PayPal generada exitosamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "order_id", type: "integer", example: 1),
                                new OA\Property(property: "paypal_order_id", type: "string", example: "5O190127TN364715T"),
                                new OA\Property(property: "approval_url", type: "string", example: "https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715T"),
                                new OA\Property(property: "amount", type: "number", format: "float", example: 250.00),
                                new OA\Property(property: "currency", type: "string", example: "USD"),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "La orden no se encuentra en estado pendiente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "La orden ya ha sido pagada o se encuentra cancelada.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Orden no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Orden no encontrada.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error de comunicación con PayPal API",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No se pudo conectar con PayPal.")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "No autenticado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No autenticado. Por favor inicie sesión.")
                    ]
                )
            )
        ]
    )]
    public function create(Request $request, int $orderId): JsonResponse
    {
        $userId = $request->user()->id;

        $order = Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $order->isPending()) {
            return response()->json([
                'success' => false,
                'message' => "La orden no está disponible para pago. Estado actual: {$order->status}.",
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $paypalResult = $this->payPalService->createOrder($order->id, (float) $order->total_price);

            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'status'   => Payment::STATUS_PENDING,
                ],
                [
                    'payment_method'   => Payment::METHOD_PAYPAL,
                    'amount'           => $order->total_price,
                    'transaction_id'   => $paypalResult['paypal_order_id'],
                    'transaction_json' => $paypalResult['raw'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Orden PayPal generada exitosamente',
                'data'    => [
                    'order_id'        => $order->id,
                    'paypal_order_id' => $paypalResult['paypal_order_id'],
                    'approval_url'    => $paypalResult['approval_url'],
                    'amount'          => (float) $order->total_price,
                    'currency'        => config('services.paypal.currency', 'USD'),
                ],
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error('Error creando orden en PayPal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al comunicar con PayPal: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/api/v1/payments/paypal/success",
        summary: "Retorno exitoso de PayPal",
        description: "URL pública a la que PayPal redirige al cliente tras aprobar el pago. Recibe el token (paypal_order_id) y PayerID como query params, captura el pago en PayPal y actualiza la orden a 'paid'.",
        tags: ["Pagos PayPal"],
        parameters: [
            new OA\Parameter(
                name: "token",
                in: "query",
                description: "ID de la orden en PayPal (paypal_order_id)",
                required: true,
                schema: new OA\Schema(type: "string", example: "5O190127TN364715T")
            ),
            new OA\Parameter(
                name: "PayerID",
                in: "query",
                description: "Identificador del comprador en PayPal",
                required: false,
                schema: new OA\Schema(type: "string", example: "CR2X5N3J4V6")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago capturado y orden confirmada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Pago completado y orden confirmada exitosamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "order_id", type: "integer", example: 1),
                                new OA\Property(property: "total_price", type: "number", format: "float", example: 250.00),
                                new OA\Property(property: "order_status", type: "string", example: "paid"),
                                new OA\Property(property: "payment_status", type: "string", example: "completed"),
                                new OA\Property(property: "transaction_id", type: "string", example: "5O190127TN364715T"),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Token inválido o fallo en la captura",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No se pudo capturar el pago en PayPal.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No se encontró una orden asociada al token",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No se encontró una orden asociada a la transacción de PayPal indicada.")
                    ]
                )
            )
        ]
    )]
    public function success(Request $request): JsonResponse
    {
        $paypalToken = $request->query('token');

        if (! $paypalToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token de PayPal no proporcionado.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $payment = Payment::where('transaction_id', $paypalToken)->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una orden asociada a la transacción de PayPal indicada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $order = $payment->order;

        // Idempotencia: si ya está pagado devolver éxito
        if ($order->isPaid() && $payment->isCompleted()) {
            return response()->json([
                'success' => true,
                'message' => 'Este pago ya había sido procesado y confirmado.',
                'data'    => [
                    'order_id'       => $order->id,
                    'order_status'   => $order->status,
                    'payment_status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                ],
            ], Response::HTTP_OK);
        }

        try {
            $captureResult = $this->payPalService->capturePayment($paypalToken);
            $captureStatus = $captureResult['status'] ?? null;

            if ($captureStatus === 'COMPLETED') {
                DB::transaction(function () use ($order, $payment, $captureResult) {
                    $payment->markAsCompleted($payment->transaction_id, $captureResult);
                    $order->markAsPaid();
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Pago completado y orden confirmada exitosamente',
                    'data'    => [
                        'order_id'       => $order->id,
                        'total_price'    => (float) $order->total_price,
                        'order_status'   => $order->fresh()->status,
                        'payment_status' => $payment->fresh()->status,
                        'transaction_id' => $payment->transaction_id,
                    ],
                ], Response::HTTP_OK);
            }

            $payment->markAsFailed($captureResult);

            return response()->json([
                'success' => false,
                'message' => "La captura de PayPal no se completó. Estado devuelto: {$captureStatus}",
                'data'    => $captureResult,
            ], Response::HTTP_BAD_REQUEST);

        } catch (Exception $e) {
            Log::error('Error capturando pago PayPal: ' . $e->getMessage());
            $payment->markAsFailed(['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al capturar el pago en PayPal: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[OA\Get(
        path: "/api/v1/payments/paypal/cancel",
        summary: "Retorno de pago cancelado por el cliente",
        description: "URL pública a la que PayPal redirige cuando el cliente cancela el proceso de pago. Informa al frontend que la orden sigue pendiente y no fue procesada.",
        tags: ["Pagos PayPal"],
        parameters: [
            new OA\Parameter(
                name: "token",
                in: "query",
                description: "Token de la orden en PayPal cancelada",
                required: false,
                schema: new OA\Schema(type: "string", example: "5O190127TN364715T")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cancelación registrada. La orden sigue pendiente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "El proceso de pago fue cancelado por el usuario. La orden sigue pendiente."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "token", type: "string", example: "5O190127TN364715T"),
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function cancel(Request $request): JsonResponse
    {
        $paypalToken = $request->query('token');

        if ($paypalToken) {
            $payment = Payment::where('transaction_id', $paypalToken)->first();
            if ($payment && $payment->isPending()) {
                $payment->update([
                    'transaction_json' => array_merge(
                        $payment->transaction_json ?? [],
                        ['cancelled_at' => now()->toIso8601String()]
                    ),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'El proceso de pago con PayPal fue cancelado. Su orden sigue registrada como pendiente.',
            'data'    => [
                'token' => $paypalToken,
            ],
        ], Response::HTTP_OK);
    }
}
