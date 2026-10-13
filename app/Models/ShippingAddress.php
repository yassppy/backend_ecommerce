<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingAddress extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: ShippingAddress
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "shipping_addresses".
    | Guarda las direcciones de envío del cliente.
    |
    | Campos de la tabla:
    | id, user_id, address, city, state, zip_code, country,
    | created_at, updated_at.
    |
    | Una dirección pertenece a un usuario.
    | Una dirección puede ser usada por muchas órdenes.
    |
    */

    protected $table = 'shipping_addresses';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | user_id:
    | Usuario dueño de la dirección.
    |
    | address:
    | Dirección exacta o referencia principal.
    |
    | city:
    | Ciudad.
    |
    | state:
    | Estado, región, departamento o provincia.
    |
    | zip_code:
    | Código postal.
    |
    | country:
    | País.
    |
    */

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: ShippingAddress pertenece a User
    |--------------------------------------------------------------------------
    |
    | Cada dirección pertenece a un usuario.
    |
    | Relación en base de datos:
    | shipping_addresses.user_id -> users.id
    |
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: ShippingAddress tiene muchas Order
    |--------------------------------------------------------------------------
    |
    | Una dirección puede ser utilizada en varias órdenes.
    |
    | Relación en base de datos:
    | orders.user_address_id -> shipping_addresses.id
    |
    | Importante:
    | En la tabla orders el campo se llama user_address_id,
    | por eso se indica manualmente como clave foránea.
    |
    */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_address_id');
    }
}
