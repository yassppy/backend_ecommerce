<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    #[OA\Get(
        path: "/api/v1/cart",
        summary: "Ver carrito del usuario",
        description: "Obtiene los productos guardados en el carrito del cliente autenticado junto con los subtotales y el total calculado.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Carrito obtenido correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Carrito obtenido correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "items", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "items_count", type: "integer", example: 2),
                                new OA\Property(property: "total_quantity", type: "integer", example: 3),
                                new OA\Property(property: "total_amount", type: "number", format: "float", example: 1250.50),
                            ]
                        )
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

        $items = Cart::where('user_id', $userId)
            ->with(['product:id,name,slug,price,price2,stock,image,status,category_id,brand_id', 'product.category:id,name', 'product.brand:id,name'])
            ->get();

        $totalAmount = $items->sum('sub_total');
        $totalQuantity = $items->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => 'Carrito obtenido correctamente',
            'data' => [
                'items' => $items,
                'items_count' => $items->count(),
                'total_quantity' => (int) $totalQuantity,
                'total_amount' => round((float) $totalAmount, 2),
            ],
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/cart",
        summary: "Agregar producto al carrito",
        description: "Agrega una cantidad determinada de un producto activo al carrito persistente del usuario. Si el producto ya existía, suma la cantidad respetando el stock.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos del producto a agregar",
            content: new OA\JsonContent(
                required: ["product_id", "quantity"],
                properties: [
                    new OA\Property(property: "product_id", type: "integer", example: 1),
                    new OA\Property(property: "quantity", type: "integer", example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Producto agregado al carrito con éxito",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto agregado al carrito exitosamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Stock insuficiente o producto inactivo",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "El stock disponible es insuficiente.")
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
    public function store(AddToCartRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $productId = (int) $request->validated('product_id');
        $quantity = (int) $request->validated('quantity');

        // Validar que el producto exista y esté activo
        $product = Product::where('id', $productId)->first();

        if (! $product || $product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'El producto solicitado no está disponible en este momento.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar si ya está en el carrito
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        $newQuantity = $cartItem ? $cartItem->quantity + $quantity : $quantity;

        // Validar stock disponible
        if ($newQuantity > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuficiente. Solo quedan {$product->stock} unidades disponibles.",
            ], Response::HTTP_BAD_REQUEST);
        }

        // Determinar precio vigente
        $unitPrice = (float) $product->price;

        if ($cartItem) {
            $cartItem->quantity = $newQuantity;
            $cartItem->price = $unitPrice;
            $cartItem->recalculateSubtotal();
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $newQuantity,
                'price' => $unitPrice,
                'sub_total' => $unitPrice * $newQuantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito exitosamente',
            'data' => $cartItem->load('product:id,name,slug,price,stock,image'),
        ], Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/api/v1/cart/{id}",
        summary: "Actualizar cantidad de un item del carrito",
        description: "Actualiza la cantidad de un producto específico dentro del carrito y recalcula el subtotal.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del item en el carrito",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Nueva cantidad para el producto",
            content: new OA\JsonContent(
                required: ["quantity"],
                properties: [
                    new OA\Property(property: "quantity", type: "integer", example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Item del carrito actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Carrito actualizado correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Stock insuficiente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "El stock disponible es insuficiente.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Item no encontrado en el carrito",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Item no encontrado en su carrito.")
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
    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $quantity = (int) $request->validated('quantity');

        $cartItem = Cart::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en su carrito.',
            ], Response::HTTP_NOT_FOUND);
        }

        $product = Product::find($cartItem->product_id);

        if (! $product || $product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'El producto ya no se encuentra disponible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($quantity > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuficiente. Solo quedan {$product->stock} unidades disponibles.",
            ], Response::HTTP_BAD_REQUEST);
        }

        $cartItem->quantity = $quantity;
        $cartItem->price = (float) $product->price;
        $cartItem->recalculateSubtotal();
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Carrito actualizado correctamente',
            'data' => $cartItem->load('product:id,name,slug,price,stock,image'),
        ], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: "/api/v1/cart/{id}",
        summary: "Eliminar item del carrito",
        description: "Elimina un producto específico del carrito del usuario autenticado.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del item en el carrito",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Item eliminado con éxito",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto eliminado del carrito correctamente"),
                        new OA\Property(property: "data", type: "null", example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Item no encontrado en el carrito",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Item no encontrado en su carrito.")
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
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $cartItem = Cart::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en su carrito.',
            ], Response::HTTP_NOT_FOUND);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito correctamente',
            'data' => null,
        ], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: "/api/v1/cart/clear",
        summary: "Vaciar carrito completo",
        description: "Elimina todos los productos del carrito del usuario autenticado.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Carrito vaciado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Carrito vaciado correctamente"),
                        new OA\Property(property: "data", type: "null", example: null)
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
    public function clear(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Cart::where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado correctamente',
            'data' => null,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/cart/summary",
        summary: "Resumen de carrito",
        description: "Obtiene los totales financieros y conteo de productos del carrito para barras superiores, widgets o previo al checkout.",
        tags: ["Carrito"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen de carrito obtenido correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Resumen obtenido correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "subtotal", type: "number", format: "float", example: 1250.50),
                                new OA\Property(property: "total", type: "number", format: "float", example: 1250.50),
                                new OA\Property(property: "items_count", type: "integer", example: 2),
                                new OA\Property(property: "total_quantity", type: "integer", example: 3),
                            ]
                        )
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
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = Cart::where('user_id', $userId)->get();

        $subtotal = round((float) $items->sum('sub_total'), 2);
        $totalQuantity = (int) $items->sum('quantity');
        $itemsCount = $items->count();

        return response()->json([
            'success' => true,
            'message' => 'Resumen obtenido correctamente',
            'data' => [
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'items_count' => $itemsCount,
                'total_quantity' => $totalQuantity,
            ],
        ], Response::HTTP_OK);
    }
}
