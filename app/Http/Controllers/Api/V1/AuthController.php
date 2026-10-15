<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/v1/auth/register",
        summary: "Registrar un nuevo cliente",
        description: "Permite a un nuevo cliente crear una cuenta en el ecommerce. Retorna la información del usuario y un token Bearer para autenticación.",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos requeridos para el registro",
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "juan@ejemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "+51987654321"),
                    new OA\Property(property: "address", type: "string", nullable: true, example: "Av. Principal 123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuario registrado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Usuario registrado correctamente"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "user",
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
                                ),
                                new OA\Property(property: "token", type: "string", example: "1|laravel_sanctum_token_string"),
                                new OA\Property(property: "token_type", type: "string", example: "Bearer"),
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
                            example: ["email" => ["El correo ya se encuentra registrado."]]
                        )
                    ]
                )
            )
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Hash automático por el cast 'hashed' del modelo User
            'phone' => $validated['phone'] ?? null,
            'role' => 'customer',
            'address' => $validated['address'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_CREATED);
    }

    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "Iniciar sesión",
        description: "Autentica al usuario mediante email y contraseña, generando un token Bearer para las peticiones protegidas.",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Credenciales de acceso",
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "juan@ejemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Inicio de sesión exitoso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Inicio de sesión exitoso"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                                        new OA\Property(property: "email", type: "string", example: "juan@ejemplo.com"),
                                        new OA\Property(property: "phone", type: "string", nullable: true, example: "+51987654321"),
                                        new OA\Property(property: "role", type: "string", example: "customer"),
                                        new OA\Property(property: "address", type: "string", nullable: true, example: "Av. Principal 123"),
                                    ]
                                ),
                                new OA\Property(property: "token", type: "string", example: "1|laravel_sanctum_token_string"),
                                new OA\Property(property: "token_type", type: "string", example: "Bearer"),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Credenciales incorrectas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Credenciales inválidas."),
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
                            example: ["email" => ["El correo electrónico es obligatorio."]]
                        )
                    ]
                )
            )
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_OK);
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "Obtener perfil del usuario autenticado",
        description: "Devuelve los datos del cliente autenticado a partir de su Bearer Token.",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Datos del usuario autenticado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Usuario obtenido correctamente"),
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
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
                    ]
                )
            )
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Usuario obtenido correctamente',
            'data' => $request->user(),
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "Cerrar sesión",
        description: "Revoca el token actual del usuario autenticado cerrando su sesión.",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sesión cerrada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Sesión cerrada correctamente"),
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
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        // Revoca el token actual con el que se realizó la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
