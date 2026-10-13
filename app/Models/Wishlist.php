<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Wishlist
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "wishlists".
    | Sirve para guardar productos favoritos o deseados por el cliente.
    |
    | Campos de la tabla:
    | id, user_id, product_id, created_at, updated_at.
    |
    | La relación es simple:
    | Un usuario puede guardar muchos productos.
    | Un producto puede estar guardado por muchos usuarios.
    |
    */

    protected $table = 'wishlists';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | user_id:
    | Usuario dueño del favorito.
    |
    | product_id:
    | Producto agregado a favoritos.
    |
    */

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Wishlist pertenece a User
    |--------------------------------------------------------------------------
    |
    | Cada favorito pertenece a un usuario.
    |
    | Relación en base de datos:
    | wishlists.user_id -> users.id
    |
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Wishlist pertenece a Product
    |--------------------------------------------------------------------------
    |
    | Cada registro de wishlist apunta a un producto específico.
    |
    | Relación en base de datos:
    | wishlists.product_id -> products.id
    |
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
