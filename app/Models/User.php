<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: User
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "users".
    | En este ecommerce, un usuario puede ser cliente o administrador.
    |
    | La tabla users tiene los campos:
    | id, name, email, email_verified_at, password, phone, role,
    | address, remember_token, created_at, updated_at.
    |
    | El campo "role" permite diferenciar:
    | - admin: usuario administrador.
    | - customer: cliente normal del ecommerce.
    |
    | Este modelo también se usará para autenticación con Laravel Sanctum.
    |
    */

    use HasApiTokens, HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | Estos campos se podrán registrar o actualizar usando create(),
    | update() o fill().
    |
    | No incluimos "id" porque es autoincremental.
    | No incluimos "remember_token" porque Laravel lo gestiona internamente.
    |
    */

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'address',
    ];

    /*
    |--------------------------------------------------------------------------
    | Campos ocultos en respuestas JSON
    |--------------------------------------------------------------------------
    |
    | Estos campos no deben enviarse al frontend.
    |
    | password:
    | Nunca debe exponerse.
    |
    | remember_token:
    | Es de uso interno de Laravel.
    |
    */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos de datos
    |--------------------------------------------------------------------------
    |
    | email_verified_at:
    | Laravel lo tratará como fecha.
    |
    | password:
    | Laravel aplicará hash automático cuando se asigne una contraseña,
    | usando el cast "hashed".
    |
    */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: User tiene muchos Cart
    |--------------------------------------------------------------------------
    |
    | Un usuario puede tener muchos productos agregados al carrito.
    |
    | Relación en base de datos:
    | cart.user_id -> users.id
    |
    | Ejemplo:
    | User::with('cartItems.product')->find(1);
    |
    */

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: User tiene muchos Wishlist
    |--------------------------------------------------------------------------
    |
    | Un usuario puede guardar muchos productos en su lista de deseos.
    |
    | Relación en base de datos:
    | wishlists.user_id -> users.id
    |
    | Ejemplo:
    | User::with('wishlists.product')->find(1);
    |
    */

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: User tiene muchas ShippingAddress
    |--------------------------------------------------------------------------
    |
    | Un usuario puede registrar una o varias direcciones de envío.
    |
    | Relación en base de datos:
    | shipping_addresses.user_id -> users.id
    |
    | Ejemplo:
    | User::with('shippingAddresses')->find(1);
    |
    */

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(ShippingAddress::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: User tiene muchas Order
    |--------------------------------------------------------------------------
    |
    | Un usuario puede realizar muchas órdenes o pedidos.
    |
    | Relación en base de datos:
    | orders.user_id -> users.id
    |
    | Esta relación será clave para el historial de compras.
    |
    */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: User tiene muchas Review
    |--------------------------------------------------------------------------
    |
    | Un usuario puede dejar reseñas en distintos productos.
    |
    | Relación en base de datos:
    | reviews.user_id -> users.id
    |
    */

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: clientes
    |--------------------------------------------------------------------------
    |
    | Este scope permite obtener únicamente usuarios con role customer.
    |
    | Ejemplo:
    | User::customers()->get();
    |
    */

    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: administradores
    |--------------------------------------------------------------------------
    |
    | Este scope permite obtener únicamente usuarios con role admin.
    |
    | Ejemplo:
    | User::admins()->get();
    |
    */

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si el usuario es administrador
    |--------------------------------------------------------------------------
    |
    | Este método será útil más adelante para middlewares,
    | validaciones o permisos administrativos.
    |
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: verificar si el usuario es cliente
    |--------------------------------------------------------------------------
    |
    | Este método será útil para validar acciones propias del cliente,
    | como carrito, wishlist, checkout y reseñas.
    |
    */

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
