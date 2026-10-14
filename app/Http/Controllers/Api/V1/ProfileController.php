<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    #[OA\Get(
        path: "/api/v1/profile",
        summary: "Mostrar datos del perfil del cliente",
        description: "Devuelve los datos del perfil del usuario cliente autenticado.",
        tags: ["Perfil"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Perfil obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Perfil obtenido correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                                new OA\Property(property: "email", type: "string", example: "juan@ejemplo.com"),
                                new OA\Property(property: "phone", type: "string", nullable: true, example: "+51987654321"),
                                new OA\Property(property: "role", type: "string", example: "customer"),
                                new OA\Property(property: "address", type: "string", nullable: true, example: "Av. Principal 123"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time"),
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
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Perfil obtenido correctamente',
            'data' => $request->user(),
        ], Response::HTTP_OK);
    }

    #[OA\Put(
        path: "/api/v1/profile",
        summary: "Actualizar datos básicos del cliente",
        description: "Permite al cliente actualizar su nombre, teléfono y dirección.",
        tags: ["Perfil"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Campos modificables del perfil",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Juan Pérez Actualizado"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "+51987654321"),
                    new OA\Property(property: "address", type: "string", nullable: true, example: "Calle Nueva 456"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Perfil actualizado correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Perfil actualizado correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Juan Pérez Actualizado"),
                                new OA\Property(property: "email", type: "string", example: "juan@ejemplo.com"),
                                new OA\Property(property: "phone", type: "string", nullable: true, example: "+51987654321"),
                                new OA\Property(property: "role", type: "string", example: "customer"),
                                new OA\Property(property: "address", type: "string", nullable: true, example: "Calle Nueva 456"),
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
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            example: ["name" => ["El nombre no puede estar vacío."]]
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
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'data' => $user->fresh(),
        ], Response::HTTP_OK);
    }

    #[OA\Put(
        path: "/api/v1/profile/password",
        summary: "Cambiar contraseña del cliente",
        description: "Permite al cliente autenticado modificar su contraseña previa validación de la contraseña actual.",
        tags: ["Perfil"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Contraseña actual y nueva contraseña con confirmación",
            content: new OA\JsonContent(
                required: ["current_password", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "current_password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newpassword456"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "newpassword456"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Contraseña actualizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Contraseña actualizada correctamente"),
                        new OA\Property(property: "data", type: "null", example: null)
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
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            example: ["current_password" => ["La contraseña actual no es correcta."]]
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
    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // El cast 'hashed' en User se encarga de aplicar hash automáticamente
        $user->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
