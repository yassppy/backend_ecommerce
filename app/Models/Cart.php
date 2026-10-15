<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Cart
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "cart".
    | La tabla cart guarda los productos agregados al carrito
    | por cada usuario autenticado.
    |
    | Campos de la tabla:
    | id, user_id, product_id, quantity, price, sub_total,
    | created_at, updated_at.
    |
    | Importante:
    | La tabla se llama "cart" en singular, por eso declaramos
    | protected $table = 'cart';
    |
    */

    protected $table = 'cart';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | user_id:
    | Usuario dueño del carrito.
    |
    | product_id:
    | Producto agregado al carrito.
    |
    | quantity:
    | Cantidad agregada.
    |
    | price:
    | Precio unitario guardado en el momento de agregar.
    |
    | sub_total:
    | Resultado de price * quantity.
    |
    */

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'sub_total',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    |
    | quantity:
    | Se manejará como entero.
    |
    | price y sub_total:
    | Se manejarán como decimal de 2 posiciones.
    |
    */

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Cart pertenece a User
    |--------------------------------------------------------------------------
    |
    | Cada item del carrito pertenece a un usuario.
    |
    | Relación en base de datos:
    | cart.user_id -> users.id
    |
    | Ejemplo:
    | Cart::with('user')->first();
    |
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Cart pertenece a Product
    |--------------------------------------------------------------------------
    |
    | Cada item del carrito corresponde a un producto.
    |
    | Relación en base de datos:
    | cart.product_id -> products.id
    |
    | Ejemplo:
    | Cart::with('product')->first();
    |
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Método auxiliar: recalcular subtotal
    |--------------------------------------------------------------------------
    |
    | Este método permite recalcular el subtotal del item.
    |
    | Lo usaremos más adelante cuando creemos el CartController.
    |
    | Fórmula:
    | sub_total = price * quantity
    |
    */

    public function recalculateSubtotal(): void
    {
        $this->sub_total = $this->price * $this->quantity;
    }
}
