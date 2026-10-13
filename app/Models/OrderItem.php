<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: OrderItem
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "order_items".
    |
    | Un order item es cada producto incluido dentro de una orden.
    |
    | Ejemplo:
    | Si una orden tiene 3 productos diferentes, entonces habrá
    | 3 registros en order_items relacionados con esa orden.
    |
    | La tabla order_items tiene los campos:
    | id, order_id, product_id, quantity, price, subtotal,
    | created_at, updated_at.
    |
    | Importante:
    | Guardamos price y subtotal en esta tabla para preservar el historial.
    | Así, aunque el precio del producto cambie después, la compra antigua
    | seguirá mostrando el precio correcto al momento de comprar.
    |
    */

    protected $table = 'order_items';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | order_id:
    | Orden a la que pertenece el item.
    |
    | product_id:
    | Producto comprado.
    |
    | quantity:
    | Cantidad comprada.
    |
    | price:
    | Precio unitario al momento de la compra.
    |
    | subtotal:
    | price * quantity.
    |
    */

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    |
    | quantity:
    | Se manejará como entero.
    |
    | price y subtotal:
    | Se manejarán como decimal con 2 posiciones.
    |
    */

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: OrderItem pertenece a Order
    |--------------------------------------------------------------------------
    |
    | Cada item pertenece a una orden.
    |
    | Relación en base de datos:
    | order_items.order_id -> orders.id
    |
    | Ejemplo:
    | OrderItem::with('order')->first();
    |
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: OrderItem pertenece a Product
    |--------------------------------------------------------------------------
    |
    | Cada item está asociado a un producto.
    |
    | Relación en base de datos:
    | order_items.product_id -> products.id
    |
    | Ejemplo:
    | OrderItem::with('product')->first();
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
    | Este método calcula nuevamente el subtotal del item.
    |
    | Fórmula:
    | subtotal = price * quantity
    |
    | Se podrá usar durante el checkout antes de guardar los detalles.
    |
    */

    public function recalculateSubtotal(): void
    {
        $this->subtotal = $this->price * $this->quantity;
    }
}
