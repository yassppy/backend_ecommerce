<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\WishlistRequest;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    #[OA\Get(
        path: "/api/v1/wishlist",
        summary: "Listar productos favoritos del usuario",
        description: "Obtiene la lista completa de productos guardados en favoritos/wishlist por el cliente autenticado.",
        tags: ["Wishlist"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Favoritos obtenidos correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Lista de deseos obtenida correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "user_id", type: "integer", example: 1),
                                    new OA\Property(property: "product_id", type: "integer", example: 5),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "product", type: "object")
                                ]
                            )
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

        $wishlists = Wishlist::where('user_id', $userId)
            ->with(['product:id,name,slug,price,price2,stock,image,status,category_id,brand_id', 'product.category:id,name', 'product.brand:id,name'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de deseos obtenida correctamente',
            'data' => $wishlists,
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/wishlist",
        summary: "Agregar producto a favoritos",
        description: "Agrega un producto a la lista de deseos del cliente si aún no está agregado.",
        tags: ["Wishlist"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "ID del producto a agregar a favoritos",
            content: new OA\JsonContent(
                required: ["product_id"],
                properties: [
                    new OA\Property(property: "product_id", type: "integer", example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Producto agregado a favoritos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto agregado a su lista de deseos"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: "El producto ya estaba en favoritos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "El producto ya se encuentra en su lista de deseos"),
                        new OA\Property(property: "data", type: "object")
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
    public function store(WishlistRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $productId = (int) $request->validated('product_id');

        $exists = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'El producto ya se encuentra en su lista de deseos',
                'data' => $exists->load('product:id,name,slug,price,image'),
            ], Response::HTTP_OK);
        }

        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado a su lista de deseos',
            'data' => $wishlist->load('product:id,name,slug,price,image'),
        ], Response::HTTP_CREATED);
    }

    #[OA\Post(
        path: "/api/v1/wishlist/toggle",
        summary: "Alternar producto en favoritos (Agregar / Quitar)",
        description: "Permite al frontend un botón de 'me gusta' tipo toggle. Si el producto ya está en favoritos lo remueve; si no está, lo agrega.",
        tags: ["Wishlist"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "ID del producto a alternar",
            content: new OA\JsonContent(
                required: ["product_id"],
                properties: [
                    new OA\Property(property: "product_id", type: "integer", example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Estado de favorito alternado correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto agregado a favoritos"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "is_favorited", type: "boolean", example: true),
                                new OA\Property(property: "product_id", type: "integer", example: 5)
                            ]
                        )
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
    public function toggle(WishlistRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $productId = (int) $request->validated('product_id');

        $item = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($item) {
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de favoritos',
                'data' => [
                    'is_favorited' => false,
                    'product_id' => $productId,
                ],
            ], Response::HTTP_OK);
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado a favoritos',
            'data' => [
                'is_favorited' => true,
                'product_id' => $productId,
            ],
        ], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: "/api/v1/wishlist/{product_id}",
        summary: "Eliminar producto de favoritos",
        description: "Elimina un producto específico de la lista de deseos mediante su product_id.",
        tags: ["Wishlist"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "product_id",
                in: "path",
                description: "ID del producto a remover de favoritos",
                required: true,
                schema: new OA\Schema(type: "integer", example: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Producto eliminado de favoritos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto eliminado de su lista de deseos"),
                        new OA\Property(property: "data", type: "null", example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "El producto no estaba en la lista de deseos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "El producto no se encuentra en su lista de deseos.")
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
    public function destroy(Request $request, int $productId): JsonResponse
    {
        $userId = $request->user()->id;

        $item = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no se encuentra en su lista de deseos.',
            ], Response::HTTP_NOT_FOUND);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado de su lista de deseos',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
