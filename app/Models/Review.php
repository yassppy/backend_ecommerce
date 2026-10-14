<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Review
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "reviews".
    |
    | Una reseña es una opinión o calificación que un usuario deja sobre
    | un producto dentro del ecommerce.
    |
    | La tabla reviews tiene los campos:
    | id, user_id, product_id, rating, comment, created_at, updated_at.
    |
    | En el MVP, este modelo nos servirá para:
    | - Mostrar reseñas públicas dentro del detalle del producto.
    | - Permitir que un cliente autenticado registre una reseña.
    | - Permitir que un cliente edite o elimine su propia reseña.
    |
    | Regla recomendada:
    | Un usuario debería poder dejar una sola reseña por producto.
    | Esta validación la manejaremos luego en el ReviewController,
    | ya que la tabla actual no tiene un índice único user_id + product_id.
    |
    */

    protected $table = 'reviews';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | user_id:
    | Usuario que creó la reseña.
    |
    | product_id:
    | Producto al que pertenece la reseña.
    |
    | rating:
    | Calificación del producto.
    | Se recomienda validar valores entre 1 y 5.
    |
    | comment:
    | Comentario escrito por el usuario.
    | Puede ser opcional según la lógica del controlador.
    |
    */

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    |
    | rating:
    | Se manejará como entero.
    |
    | Esto será útil para calcular promedios, validar rangos
    | y generar resúmenes de calificación por producto.
    |
    */

    protected $casts = [
        'rating' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Review pertenece a User
    |--------------------------------------------------------------------------
    |
    | Cada reseña pertenece a un usuario.
    |
    | Relación en base de datos:
    | reviews.user_id -> users.id
    |
    | Ejemplo:
    | Review::with('user')->first();
    |
    | Esta relación permitirá mostrar quién escribió la reseña,
    | por ejemplo:
    | - nombre del cliente
    | - fecha del comentario
    | - calificación otorgada
    |
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Review pertenece a Product
    |--------------------------------------------------------------------------
    |
    | Cada reseña pertenece a un producto específico.
    |
    | Relación en base de datos:
    | reviews.product_id -> products.id
    |
    | Ejemplo:
    | Review::with('product')->first();
    |
    | Esta relación será usada principalmente en:
    | - detalle del producto
    | - listado de reseñas
    | - cálculo de rating promedio
    |
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: reseñas con mejor calificación
    |--------------------------------------------------------------------------
    |
    | Permite obtener reseñas con una calificación mínima.
    |
    | Ejemplo:
    | Review::withMinRating(4)->get();
    |
    | Esto podría servir más adelante para mostrar opiniones positivas
    | o filtrar reseñas en el frontend.
    |
    */

    public function scopeWithMinRating($query, int $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: reseñas de un producto
    |--------------------------------------------------------------------------
    |
    | Permite filtrar reseñas por producto.
    |
    | Ejemplo:
    | Review::forProduct(10)->get();
    |
    */

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: reseñas de un usuario
    |--------------------------------------------------------------------------
    |
    | Permite filtrar reseñas hechas por un usuario.
    |
    | Ejemplo:
    | Review::byUser(5)->get();
    |
    */

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar dueño de la reseña
    |--------------------------------------------------------------------------
    |
    | Este método nos ayudará más adelante en el ReviewController.
    |
    | Sirve para validar que solo el usuario que creó la reseña
    | pueda editarla o eliminarla.
    |
    | Ejemplo:
    | if (! $review->belongsToUser(auth()->id())) {
    |     abort(403);
    | }
    |
    */

    public function belongsToUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
