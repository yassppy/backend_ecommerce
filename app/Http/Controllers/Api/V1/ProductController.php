<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    #[OA\Get(
        path: "/api/v1/products",
        summary: "Listar catálogo de productos activos",
        description: "Obtiene los productos activos con paginación, filtros por búsqueda, categoría, marca, rango de precios y ordenamiento.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Buscar por nombre o descripción del producto",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "category_id",
                in: "query",
                description: "Filtrar por ID de categoría",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "brand_id",
                in: "query",
                description: "Filtrar por ID de marca",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "min_price",
                in: "query",
                description: "Precio mínimo",
                required: false,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "max_price",
                in: "query",
                description: "Precio máximo",
                required: false,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "sort",
                in: "query",
                description: "Criterio de orden: price_asc, price_desc, latest",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["price_asc", "price_desc", "latest"])
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Productos por página (por defecto 12)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 12)
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
                description: "Listado de productos obtenido correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Productos obtenidos correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->active()
            ->with(['category', 'brand']);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Filtro por marca
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->query('brand_id'));
        }

        // Rango de precio
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        // Ordenamiento
        $sort = $request->query('sort');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'latest' => $query->latest('id'),
            default => $query->latest('id'),
        };

        $perPage = (int) $request->query('per_page', 12);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Productos obtenidos correctamente',
            'data' => $products,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/products/featured",
        summary: "Productos destacados o recientes para portada",
        description: "Obtiene los productos activos más recientes o destacados para mostrar en la página de inicio o banners.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Límite de productos a retornar (por defecto 8)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 8)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Productos destacados obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Productos destacados obtenidos correctamente"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 8);

        $products = Product::query()
            ->active()
            ->with(['category', 'brand'])
            ->latest('id')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Productos destacados obtenidos correctamente',
            'data' => $products,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/products/search",
        summary: "Búsqueda rápida de productos",
        description: "Permite una búsqueda directa en catálogo activo por nombre, descripción o coincidencia de categoría/marca.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "q",
                in: "query",
                description: "Texto a buscar (requerido)",
                required: true,
                schema: new OA\Schema(type: "string", example: "laptop")
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Límite de resultados (por defecto 10)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resultados de búsqueda",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Búsqueda completada exitosamente"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Parámetro q no proporcionado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "El parámetro de búsqueda 'q' es obligatorio.")
                    ]
                )
            )
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q');

        if (! $searchTerm || trim($searchTerm) === '') {
            return response()->json([
                'success' => false,
                'message' => "El parámetro de búsqueda 'q' es obligatorio.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $limit = (int) $request->query('limit', 10);

        $products = Product::query()
            ->active()
            ->with(['category', 'brand'])
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('description', 'ilike', "%{$searchTerm}%")
                    ->orWhereHas('category', function ($q) use ($searchTerm) {
                        $q->where('name', 'ilike', "%{$searchTerm}%");
                    })
                    ->orWhereHas('brand', function ($q) use ($searchTerm) {
                        $q->where('name', 'ilike', "%{$searchTerm}%");
                    });
            })
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Búsqueda completada exitosamente',
            'data' => $products,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/products/{slug}",
        summary: "Detalle completo de producto",
        description: "Obtiene el detalle completo de un producto activo mediante su slug único, incluyendo categoría, marca y resumen de calificaciones/reseñas.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                description: "Slug único del producto",
                required: true,
                schema: new OA\Schema(type: "string", example: "laptop-dell-xps-13")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle del producto obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Producto obtenido correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Producto no encontrado")
                    ]
                )
            )
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with(['category', 'brand'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], Response::HTTP_NOT_FOUND);
        }

        // Estructurar resumen de reseñas
        $product->reviews_summary = [
            'total_reviews' => $product->reviews_count ?? 0,
            'average_rating' => $product->reviews_avg_rating ? round((float) $product->reviews_avg_rating, 1) : 0,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Producto obtenido correctamente',
            'data' => $product,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/products/{product_id}/reviews",
        summary: "Listar reseñas visibles de un producto",
        description: "Obtiene las opiniones y calificaciones dejadas por los usuarios en el producto especificado, con paginación.",
        tags: ["Reseñas"],
        parameters: [
            new OA\Parameter(
                name: "product_id",
                in: "path",
                description: "ID del producto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Reseñas por página (por defecto 10)",
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
                description: "Reseñas del producto obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Reseñas obtenidas correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Producto no encontrado")
                    ]
                )
            )
        ]
    )]
    public function reviews(Request $request, int $productId): JsonResponse
    {
        $product = Product::where('id', $productId)->active()->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], Response::HTTP_NOT_FOUND);
        }

        $perPage = (int) $request->query('per_page', 10);

        $reviews = $product->reviews()
            ->with(['user:id,name'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Reseñas obtenidas correctamente',
            'data' => $reviews,
        ], Response::HTTP_OK);
    }
}
