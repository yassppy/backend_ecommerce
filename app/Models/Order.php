<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Order
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "orders".
    |
    | Una orden es el pedido principal generado por el cliente
    | después de confirmar su carrito en el checkout.
    |
    | La tabla orders tiene los campos:
    | id, user_id, user_address_id, total_price, status,
    | created_at, updated_at.
    |
    | Estados disponibles actualmente:
    | - pending: orden creada, pero aún no pagada.
    | - paid: orden pagada correctamente.
    | - shipped: orden enviada.
    | - cancelled: orden cancelada.
    |
    | En el MVP, cuando el cliente confirme el checkout,
    | la orden iniciará con status "pending".
    |
    | Cuando PayPal confirme el pago correctamente,
    | la orden pasará a status "paid".
    |
    */

    protected $table = 'orders';

    /*
    |--------------------------------------------------------------------------
    | Constantes de estado
    |--------------------------------------------------------------------------
    |
    | Usamos constantes para evitar escribir strings manualmente
    | en controladores o servicios.
    |
    | Esto reduce errores como escribir "payed" en vez de "paid".
    |
    */

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | user_id:
    | Usuario que realizó la compra.
    |
    | user_address_id:
    | Dirección de envío seleccionada por el usuario.
    | Puede ser null si la dirección fue eliminada luego,
    | porque la FK está configurada con ON DELETE SET NULL.
    |
    | total_price:
    | Total final de la orden.
    |
    | status:
    | Estado actual de la orden.
    |
    */

    protected $fillable = [
        'user_id',
        'user_address_id',
        'total_price',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    |
    | total_price:
    | Se manejará como decimal con 2 posiciones.
    |
    */

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Order pertenece a User
    |--------------------------------------------------------------------------
    |
    | Cada orden pertenece a un usuario.
    |
    | Relación en base de datos:
    | orders.user_id -> users.id
    |
    | Ejemplo:
    | Order::with('user')->first();
    |
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Order pertenece a ShippingAddress
    |--------------------------------------------------------------------------
    |
    | Cada orden puede tener una dirección de envío asociada.
    |
    | Relación en base de datos:
    | orders.user_address_id -> shipping_addresses.id
    |
    | Importante:
    | El campo no se llama shipping_address_id, sino user_address_id,
    | por eso declaramos manualmente la clave foránea.
    |
    */

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class, 'user_address_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Order tiene muchos OrderItem
    |--------------------------------------------------------------------------
    |
    | Una orden puede contener muchos productos comprados.
    |
    | Relación en base de datos:
    | order_items.order_id -> orders.id
    |
    | Ejemplo:
    | Order::with('items.product')->find(1);
    |
    */

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Order tiene muchos Payment
    |--------------------------------------------------------------------------
    |
    | La tabla payments permite registrar pagos asociados a una orden.
    |
    | Aunque en el MVP normalmente tendremos un solo pago por orden,
    | esta relación permite guardar varios intentos si PayPal falla,
    | se cancela o se vuelve a intentar.
    |
    | Relación en base de datos:
    | payments.order_id -> orders.id
    |
    */

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: último pago de la orden
    |--------------------------------------------------------------------------
    |
    | Esta relación devuelve el último pago registrado para la orden.
    |
    | Será útil en el detalle de pedido para mostrar el estado más reciente
    | del pago sin traer todos los intentos.
    |
    */

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id')->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: órdenes pendientes
    |--------------------------------------------------------------------------
    |
    | Permite consultar órdenes pendientes.
    |
    | Ejemplo:
    | Order::pending()->get();
    |
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: órdenes pagadas
    |--------------------------------------------------------------------------
    |
    | Permite consultar órdenes pagadas.
    |
    | Ejemplo:
    | Order::paid()->get();
    |
    */

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si la orden está pendiente
    |--------------------------------------------------------------------------
    |
    | Será útil antes de iniciar un pago con PayPal.
    | Solo una orden pending debería poder enviarse a PayPal.
    |
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si la orden está pagada
    |--------------------------------------------------------------------------
    |
    | Será útil para evitar pagos duplicados.
    |
    */

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: marcar orden como pagada
    |--------------------------------------------------------------------------
    |
    | Este método centraliza el cambio de estado a "paid".
    | Lo usaremos más adelante al confirmar el pago con PayPal.
    |
    */

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: marcar orden como cancelada
    |--------------------------------------------------------------------------
    |
    | Este método será útil cuando el cliente cancele una orden pendiente.
    |
    */

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }
}
