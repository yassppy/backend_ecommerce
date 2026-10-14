<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    #[OA\Get(
        path: "/api/v1/brands",
        summary: "Listar todas las marcas",
        description: "Obtiene el listado completo de marcas disponibles con el conteo de productos activos. Permite filtrar por búsqueda y paginar.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Término de búsqueda por nombre o descripción de la marca",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Cantidad de marcas por página (si no se envía o es nulo, devuelve lista completa)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Marcas obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Marcas obtenidas correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Apple"),
                                    new OA\Property(property: "slug", type: "string", example: "apple"),
                                    new OA\Property(property: "description", type: "string", nullable: true, example: "Dispositivos electrónicos de Apple"),
                                    new OA\Property(property: "products_count", type: "integer", example: 8)
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
        $query = Brand::query()->withCount(['products' => function ($q) {
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
            $brands = $query->paginate((int) $request->query('per_page'));
        } else {
            $brands = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Marcas obtenidas correctamente',
            'data' => $brands,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/brands/{slug}",
        summary: "Mostrar una marca por su slug",
        description: "Obtiene los detalles de una marca específica según su slug único.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                description: "Slug único de la marca",
                required: true,
                schema: new OA\Schema(type: "string", example: "apple")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Marca obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Marca obtenida correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Apple"),
                                new OA\Property(property: "slug", type: "string", example: "apple"),
                                new OA\Property(property: "description", type: "string", nullable: true, example: "Dispositivos electrónicos"),
                                new OA\Property(property: "products_count", type: "integer", example: 8)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Marca no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Marca no encontrada")
                    ]
                )
            )
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)
            ->withCount(['products' => function ($q) {
                $q->active();
            }])
            ->first();

        if (! $brand) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marca obtenida correctamente',
            'data' => $brand,
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/brands/{slug}/products",
        summary: "Listar productos activos de una marca",
        description: "Obtiene los productos activos pertenecientes a la marca especificada por slug con soporte para paginación y ordenamiento.",
        tags: ["Catálogo"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                description: "Slug único de la marca",
                required: true,
                schema: new OA\Schema(type: "string", example: "apple")
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
                description: "Productos de la marca obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Productos de la marca obtenidos correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Marca no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Marca no encontrada")
                    ]
                )
            )
        ]
    )]
    public function products(Request $request, string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)->first();

        if (! $brand) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        $query = $brand->products()
            ->active()
            ->with(['category']);

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
            'message' => 'Productos de la marca obtenidos correctamente',
            'data' => $products,
        ], Response::HTTP_OK);
    }
}
