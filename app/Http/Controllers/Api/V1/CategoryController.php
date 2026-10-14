<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    #[OA\Get(
        path: "/api/v1/categories",
        summary: "Listar todas las categorías",
        description: "Obtiene la lista de todas las categorías de productos disponibles en el catálogo. Permite filtrar por búsqueda y paginar o listar completas.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Término de búsqueda por nombre o descripción de categoría",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Cantidad de elementos por página (si no se envía o es 'all', devuelve la lista completa)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de categorías obtenido correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Categorías obtenidas correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Laptops"),
                                    new OA\Property(property: "slug", type: "string", example: "laptops"),
                                    new OA\Property(property: "description", type: "string", nullable: true, example: "Computadoras portátiles de alto rendimiento"),
                                    new OA\Property(property: "products_count", type: "integer", example: 12)
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->withCount(['products' => function ($q) {
            $q->active();
        }]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('per_page') && is_numeric($request->query('per_page'))) {
            $categories = $query->paginate((int) $request->query('per_page'));
        } else {
            $categories = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Categorías obtenidas correctamente',
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/categories/{slug}",
        summary: "Mostrar una categoría por su slug",
        description: "Obtiene los detalles de una categoría específica usando su slug único.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                description: "Slug único de la categoría",
                required: true,
                schema: new OA\Schema(type: "string", example: "laptops")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría obtenida correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Categoría obtenida correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Laptops"),
                                new OA\Property(property: "slug", type: "string", example: "laptops"),
                                new OA\Property(property: "description", type: "string", nullable: true, example: "Computadoras portátiles"),
                                new OA\Property(property: "products_count", type: "integer", example: 12)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Categoría no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Categoría no encontrada")
                    ]
                )
            )
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->withCount(['products' => function ($q) {
                $q->active();
            }])
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoría obtenida correctamente',
            'data' => $category,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/categories/{slug}/products",
        summary: "Listar productos activos de una categoría",
        description: "Obtiene los productos activos pertenecientes a la categoría indicada por slug con soporte para paginación y ordenamiento.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                description: "Slug único de la categoría",
                required: true,
                schema: new OA\Schema(type: "string", example: "laptops")
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
                description: "Cantidad de productos por página (default: 12)",
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
                description: "Productos de la categoría obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Productos de la categoría obtenidos correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Categoría no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Categoría no encontrada")
                    ]
                )
            )
        ]
    )]
    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        $query = $category->products()
            ->active()
            ->with(['brand']);

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
            'message' => 'Productos de la categoría obtenidos correctamente',
            'data' => $products,
        ], Response::HTTP_OK);
    }
}
