<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingAddress\StoreShippingAddressRequest;
use App\Http\Requests\ShippingAddress\UpdateShippingAddressRequest;
use App\Models\ShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ShippingAddressController extends Controller
{
    #[OA\Get(
        path: "/api/v1/shipping-addresses",
        summary: "Listar direcciones de envío del usuario",
        description: "Obtiene todas las direcciones de envío registradas por el cliente autenticado.",
        tags: ["Direcciones de Envío"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Direcciones de envío obtenidas correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Direcciones de envío obtenidas correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "user_id", type: "integer", example: 1),
                                    new OA\Property(property: "address", type: "string", example: "Av. Las Camelias 450"),
                                    new OA\Property(property: "city", type: "string", example: "Lima"),
                                    new OA\Property(property: "state", type: "string", example: "Lima"),
                                    new OA\Property(property: "zip_code", type: "string", example: "15046"),
                                    new OA\Property(property: "country", type: "string", example: "Perú"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time"),
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

        $addresses = ShippingAddress::where('user_id', $userId)
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Direcciones de envío obtenidas correctamente',
            'data' => $addresses,
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/shipping-addresses",
        summary: "Registrar nueva dirección de envío",
        description: "Crea una nueva dirección de envío asociada a la cuenta del usuario autenticado.",
        tags: ["Direcciones de Envío"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos de la nueva dirección de envío",
            content: new OA\JsonContent(
                required: ["address", "city", "state", "zip_code", "country"],
                properties: [
                    new OA\Property(property: "address", type: "string", example: "Av. Las Camelias 450 Dpto 301"),
                    new OA\Property(property: "city", type: "string", example: "Lima"),
                    new OA\Property(property: "state", type: "string", example: "Lima"),
                    new OA\Property(property: "zip_code", type: "string", example: "15046"),
                    new OA\Property(property: "country", type: "string", example: "Perú"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Dirección de envío registrada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío registrada correctamente"),
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
    public function store(StoreShippingAddressRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        $address = ShippingAddress::create([
            'user_id' => $userId,
            ...$request->validated(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección de envío registrada correctamente',
            'data' => $address,
        ], Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: "/api/v1/shipping-addresses/{id}",
        summary: "Mostrar una dirección de envío",
        description: "Obtiene los detalles de una dirección de envío específica perteneciente al usuario autenticado.",
        tags: ["Direcciones de Envío"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la dirección de envío",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dirección obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío obtenida correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Dirección no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío no encontrada.")
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

        $address = ShippingAddress::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Dirección de envío no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dirección de envío obtenida correctamente',
            'data' => $address,
        ], Response::HTTP_OK);
    }

    #[OA\Put(
        path: "/api/v1/shipping-addresses/{id}",
        summary: "Actualizar una dirección de envío",
        description: "Actualiza los datos de una dirección de envío existente perteneciente al usuario autenticado.",
        tags: ["Direcciones de Envío"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la dirección de envío",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Campos a actualizar de la dirección",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "address", type: "string", example: "Av. Las Begonias 789"),
                    new OA\Property(property: "city", type: "string", example: "Lima"),
                    new OA\Property(property: "state", type: "string", example: "Lima"),
                    new OA\Property(property: "zip_code", type: "string", example: "15046"),
                    new OA\Property(property: "country", type: "string", example: "Perú"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Dirección de envío actualizada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío actualizada correctamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Dirección no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío no encontrada.")
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
    public function update(UpdateShippingAddressRequest $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $address = ShippingAddress::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Dirección de envío no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $address->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Dirección de envío actualizada correctamente',
            'data' => $address->fresh(),
        ], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: "/api/v1/shipping-addresses/{id}",
        summary: "Eliminar una dirección de envío",
        description: "Elimina una dirección de envío propia del usuario autenticado. Si la dirección ya está vinculada a órdenes históricas, se protege la integridad referencial.",
        tags: ["Direcciones de Envío"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la dirección de envío",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dirección eliminada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío eliminada correctamente"),
                        new OA\Property(property: "data", type: "null", example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "La dirección no puede ser eliminada porque está asociada a órdenes",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No se puede eliminar la dirección porque está asociada a órdenes previas.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Dirección no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Dirección de envío no encontrada.")
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

        $address = ShippingAddress::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Dirección de envío no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Proteger si ya tiene órdenes asociadas
        if ($address->orders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la dirección porque está asociada a órdenes registradas.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dirección de envío eliminada correctamente',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
