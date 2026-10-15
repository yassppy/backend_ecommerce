<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    #[OA\Get(
        path: "/api/v1/orders",
        summary: "Listar historial de órdenes del cliente",
        description: "Obtiene el listado cronológico de pedidos realizados por el usuario autenticado, con soporte para filtrado por estado y paginación.",
        tags: ["Órdenes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrar por estado: pending, paid, shipped, cancelled",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["pending", "paid", "shipped", "cancelled"])
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Cantidad de órdenes por página (por defecto 10)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Número de página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Historial de órdenes obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Órdenes obtenidas correctamente"),
                        new OA\Property(property: "data", type: "object")
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

        $query = Order::where('user_id', $userId)
            ->with(['shippingAddress', 'latestPayment'])
            ->withCount('items')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Órdenes obtenidas correctamente',
            'data' => $orders,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/orders/{id}",
        summary: "Ver detalle completo de una orden",
        description: "Obtiene la información detallada de una compra específica: productos comprados con precio histórico, dirección de envío y estado de pagos.",
        tags: ["Órdenes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la orden",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle de orden obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Orden obtenida correctamente"),
                        new OA\Property(property: "data", type: "object")
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
    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $order = Order::where('id', $id)
            ->where('user_id', $userId)
            ->with([
                'items.product:id,name,slug,image,price',
                'shippingAddress',
                'payments' => function ($q) {
                    $q->latest('id');
                },
            ])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden obtenida correctamente',
            'data' => $order,
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/orders/{id}/cancel",
        summary: "Cancelar una orden pendiente",
        description: "Permite al cliente cancelar una orden que aún se encuentra en estado pending. Devuelve el stock reservado al catálogo y marca los pagos asociados como fallidos/cancelados.",
        tags: ["Órdenes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la orden a cancelar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Orden cancelada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Orden cancelada correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "La orden no se puede cancelar porque ya está pagada o procesada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Solo se pueden cancelar órdenes en estado pendiente.")
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
    public function cancel(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $order = Order::where('id', $id)
            ->where('user_id', $userId)
            ->with('items.product')
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Regla técnica del documento: una orden pagada no debe cancelarse desde frontend cliente
        if (! $order->isPending()) {
            return response()->json([
                'success' => false,
                'message' => "No se puede cancelar una orden con estado '{$order->status}'. Solo se pueden cancelar órdenes pendientes.",
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::transaction(function () use ($order) {
            // 1. Restaurar stock al catálogo de productos
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            // 2. Marcar orden como cancelada
            $order->markAsCancelled();

            // 3. Marcar pagos pendientes como failed
            $order->payments()->where('status', 'pending')->update([
                'status' => 'failed',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada correctamente',
            'data' => $order->fresh(['items.product', 'latestPayment']),
        ], Response::HTTP_OK);
    }
}
