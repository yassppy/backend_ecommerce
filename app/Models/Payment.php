<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Payment
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "payments".
    |
    | Un pago está asociado a una orden.
    |
    | Para el MVP, el método principal de pago será PayPal.
    | Más adelante se podrían activar stripe o bank_transfer, porque
    | la base de datos ya contempla esos valores.
    |
    | La tabla payments tiene los campos:
    | id, order_id, payment_method, amount, transaction_id,
    | transaction_json, status, created_at, updated_at.
    |
    | Estados disponibles:
    | - pending: pago creado, pero aún no confirmado.
    | - completed: pago confirmado correctamente.
    | - failed: pago fallido.
    |
    | En PayPal:
    | - Al crear la orden de pago, guardaremos status pending.
    | - Al capturar correctamente, cambiaremos a completed.
    | - Si PayPal rechaza o falla, cambiaremos a failed.
    |
    */

    protected $table = 'payments';

    /*
    |--------------------------------------------------------------------------
    | Constantes de métodos de pago
    |--------------------------------------------------------------------------
    |
    | Para el MVP usaremos PAYMENT_METHOD_PAYPAL.
    |
    */

    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /*
    |--------------------------------------------------------------------------
    | Constantes de estado
    |--------------------------------------------------------------------------
    |
    | Usar constantes ayuda a evitar errores de escritura en controladores,
    | servicios o integraciones externas.
    |
    */

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | order_id:
    | Orden asociada al pago.
    |
    | payment_method:
    | Método de pago usado. Para este MVP será "paypal".
    |
    | amount:
    | Monto pagado o monto pendiente de pago.
    |
    | transaction_id:
    | Identificador de la transacción en PayPal.
    |
    | transaction_json:
    | Respuesta completa de PayPal guardada para auditoría.
    |
    | status:
    | Estado actual del pago.
    |
    */

    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'transaction_id',
        'transaction_json',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    |
    | amount:
    | Se manejará como decimal con 2 posiciones.
    |
    | transaction_json:
    | Se convertirá automáticamente entre JSON y array PHP.
    | Esto nos permitirá guardar respuestas completas de PayPal.
    |
    */

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_json' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Payment pertenece a Order
    |--------------------------------------------------------------------------
    |
    | Cada pago pertenece a una orden.
    |
    | Relación en base de datos:
    | payments.order_id -> orders.id
    |
    | Ejemplo:
    | Payment::with('order')->first();
    |
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: pagos pendientes
    |--------------------------------------------------------------------------
    |
    | Permite consultar pagos pendientes.
    |
    | Ejemplo:
    | Payment::pending()->get();
    |
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: pagos completados
    |--------------------------------------------------------------------------
    |
    | Permite consultar pagos completados.
    |
    | Ejemplo:
    | Payment::completed()->get();
    |
    */

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: pagos fallidos
    |--------------------------------------------------------------------------
    |
    | Permite consultar pagos fallidos.
    |
    | Ejemplo:
    | Payment::failed()->get();
    |
    */

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si el pago está pendiente
    |--------------------------------------------------------------------------
    |
    | Será útil para saber si todavía puede intentarse el pago.
    |
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si el pago fue completado
    |--------------------------------------------------------------------------
    |
    | Será útil para evitar capturas duplicadas en PayPal.
    |
    */

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: marcar pago como completado
    |--------------------------------------------------------------------------
    |
    | Este método se usará cuando PayPal confirme correctamente
    | el pago mediante success o webhook.
    |
    | $transactionId:
    | ID de transacción devuelto por PayPal.
    |
    | $transactionJson:
    | Respuesta completa de PayPal.
    |
    */

    public function markAsCompleted(?string $transactionId = null, ?array $transactionJson = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_id' => $transactionId,
            'transaction_json' => $transactionJson,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: marcar pago como fallido
    |--------------------------------------------------------------------------
    |
    | Este método se usará si PayPal responde con error,
    | si el webhook indica fallo o si la captura no se completa.
    |
    */

    public function markAsFailed(?array $transactionJson = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'transaction_json' => $transactionJson,
        ]);
    }
}
