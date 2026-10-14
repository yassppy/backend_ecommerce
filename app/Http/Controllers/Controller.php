<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Ecommerce API REST",
    description: "API REST profesional para Ecommerce en Laravel 13 con autenticación Sanctum y PostgreSQL (Neon).",
    contact: new OA\Contact(
        name: "Soporte Técnico API",
        email: "soporte@ecommerce.test"
    )
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Servidor Local"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Ingrese su token en formato: Bearer {token}"
)]
#[OA\Tag(
    name: "Auth",
    description: "Endpoints de autenticación de clientes y usuarios"
)]
#[OA\Tag(
    name: "Perfil",
    description: "Endpoints para la gestión del perfil del cliente"
)]
#[OA\Tag(
    name: "Catálogo",
    description: "Endpoints públicos para categorías, marcas y navegación del catálogo"
)]
#[OA\Tag(
    name: "Productos",
    description: "Endpoints públicos para catálogo, búsqueda, destacados y detalle de productos"
)]
#[OA\Tag(
    name: "Reseñas",
    description: "Endpoints para calificaciones y reseñas de productos"
)]
#[OA\Tag(
    name: "Carrito",
    description: "Endpoints para la gestión del carrito de compras del cliente autenticado"
)]
#[OA\Tag(
    name: "Wishlist",
    description: "Endpoints para la gestión de productos favoritos (lista de deseos) del cliente"
)]
#[OA\Tag(
    name: "Direcciones de Envío",
    description: "Endpoints para la gestión de direcciones de envío del cliente"
)]
abstract class Controller
{
    //
}
