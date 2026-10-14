<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    #[OA\Get(
        path: "/api/v1/checkout",
        summary: "Obtener resumen previo al checkout",
        description: "Obtiene el carrito actual con validación de stock vigente, las direcciones de envío registradas por el cliente y el resumen financiero para preparar el pago.",
        tags: ["Checkout"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen de checkout obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Resumen de checkout obtenido correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "cart_items", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "shipping_addresses", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "items_count", type: "integer", example: 2),
                                new OA\Property(property: "total_quantity", type: "integer", example: 3),
                                new OA\Property(property: "subtotal", type: "number", format: "float", example: 250.00),
                                new OA\Property(property: "total", type: "number", format: "float", example: 250.00),
                                new OA\Property(property: "can_checkout", type: "boolean", example: true),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "El carrito está vacío",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Su carrito de compras está vacío.")
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
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $cartItems = Cart::where('user_id', $userId)
            ->with(['product:id,name,slug,price,stock,image,status'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Su carrito de compras está vacío.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $addresses = ShippingAddress::where('user_id', $userId)
            ->latest('id')
            ->get();

        $subtotal = round((float) $cartItems->sum('sub_total'), 2);
        $totalQuantity = (int) $cartItems->sum('quantity');

        // Comprobar si hay algún problema de stock o producto inactivo
        $hasStockIssues = $cartItems->contains(function ($item) {
            return ! $item->product || $item->product->status !== 'active' || $item->quantity > $item->product->stock;
        });

        return response()->json([
            'success' => true,
            'message' => 'Resumen de checkout obtenido correctamente',
            'data' => [
                'cart_items' => $cartItems,
                'shipping_addresses' => $addresses,
                'items_count' => $cartItems->count(),
                'total_quantity' => $totalQuantity,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'can_checkout' => ! $hasStockIssues,
                'has_stock_issues' => $hasStockIssues,
            ],
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/checkout/place-order",
        summary: "Confirmar pedido y crear orden (Place Order)",
        description: "Valida stock, crea la orden en estado pending, copia los productos comprados a order_items preservando precio histórico, genera el registro de pago pending y vacía el carrito.",
        tags: ["Checkout"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dirección de envío y método de pago",
            content: new OA\JsonContent(
                required: ["shipping_address_id"],
                properties: [
                    new OA\Property(property: "shipping_address_id", type: "integer", example: 1),
                    new OA\Property(property: "payment_method", type: "string", enum: ["paypal", "stripe", "bank_transfer"], example: "paypal"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Orden creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Orden generada exitosamente. Lista para procesar el pago."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "order_id", type: "integer", example: 10),
                                new OA\Property(property: "total_price", type: "number", format: "float", example: 250.00),
                                new OA\Property(property: "status", type: "string", example: "pending"),
                                new OA\Property(property: "payment_method", type: "string", example: "paypal"),
                                new OA\Property(property: "payment_status", type: "string", example: "pending"),
                                new OA\Property(property: "order", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Carrito vacío, stock insuficiente o dirección inválida",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "El stock de uno o más productos es insuficiente.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Error de validación"),
                        new OA\Property(property: "errors", type: "object")
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
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $addressId = (int) $request->validated('shipping_address_id');
        $paymentMethod = $request->validated('payment_method', Payment::METHOD_PAYPAL);

        // Verificar que la dirección de envío pertenezca al usuario
        $address = ShippingAddress::where('id', $addressId)
            ->where('user_id', $userId)
            ->first();

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'La dirección de envío seleccionada no pertenece a su cuenta.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Obtener items del carrito
        $cartItems = Cart::where('user_id', $userId)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No puede realizar checkout con un carrito vacío.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Transacción de base de datos para atomicidad completa
        try {
            $order = DB::transaction(function () use ($userId, $addressId, $paymentMethod, $cartItems) {
                $totalPrice = 0;

                // 1. Validar productos y stock
                foreach ($cartItems as $item) {
                    $product = $item->product;

                    if (! $product || $product->status !== 'active') {
                        throw new \Exception("El producto '{$product?->name}' no está disponible.");
                    }

                    if ($item->quantity > $product->stock) {
                        throw new \Exception("Stock insuficiente para el producto '{$product->name}'. Quedan {$product->stock} unidades.");
                    }

                    $totalPrice += ((float) $product->price) * $item->quantity;
                }

                // 2. Crear la orden en estado pending
                $order = Order::create([
                    'user_id' => $userId,
                    'user_address_id' => $addressId,
                    'total_price' => round($totalPrice, 2),
                    'status' => Order::STATUS_PENDING,
                ]);

                // 3. Crear los order_items y descontar stock
                foreach ($cartItems as $item) {
                    $product = $item->product;
                    $unitPrice = (float) $product->price;
                    $itemSubtotal = $unitPrice * $item->quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'price' => $unitPrice,
                        'subtotal' => round($itemSubtotal, 2),
                    ]);

                    // Descontar stock del catálogo
                    $product->decrement('stock', $item->quantity);
                }

                // 4. Crear el registro de pago en pending
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $order->total_price,
                    'status' => Payment::STATUS_PENDING,
                ]);

                // 5. Vaciar el carrito de compras
                Cart::where('user_id', $userId)->delete();

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden generada exitosamente. Lista para procesar el pago.',
                'data' => [
                    'order_id' => $order->id,
                    'total_price' => (float) $order->total_price,
                    'status' => $order->status,
                    'payment_method' => $paymentMethod,
                    'payment_status' => Payment::STATUS_PENDING,
                    'order' => $order->load(['items.product:id,name,slug,price,image', 'shippingAddress', 'latestPayment']),
                ],
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
