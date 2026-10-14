<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Product
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "products".
    | Es una de las tablas principales del ecommerce.
    |
    | Cada producto pertenece a una categoría y a una marca.
    |
    | La tabla products tiene los campos:
    | id, category_id, brand_id, name, slug, description, price,
    | price2, stock, image, status, created_at, updated_at.
    |
    | El campo status permite controlar si el producto está activo
    | o inactivo dentro del catálogo.
    |
    */

    protected $table = 'products';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | price:
    | Precio principal del producto.
    |
    | price2:
    | Precio alternativo o promocional.
    |
    | stock:
    | Cantidad disponible.
    |
    | image:
    | Ruta o nombre de imagen del producto.
    |
    | status:
    | Puede ser "active" o "inactive".
    |
    */

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'price',
        'price2',
        'stock',
        'image',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos de datos
    |--------------------------------------------------------------------------
    |
    | Laravel puede convertir automáticamente ciertos campos.
    |
    | price y price2 se manejarán como decimal con 2 posiciones.
    | stock se manejará como entero.
    |
    */

    protected $casts = [
        'price' => 'decimal:2',
        'price2' => 'decimal:2',
        'stock' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Product pertenece a Category
    |--------------------------------------------------------------------------
    |
    | Cada producto pertenece a una categoría.
    |
    | Relación en base de datos:
    | products.category_id -> categories.id
    |
    | Ejemplo:
    | Product::with('category')->first();
    |
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Product pertenece a Brand
    |--------------------------------------------------------------------------
    |
    | Cada producto pertenece a una marca.
    |
    | Relación en base de datos:
    | products.brand_id -> brands.id
    |
    | Ejemplo:
    | Product::with('brand')->first();
    |
    */

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Product tiene muchos Cart
    |--------------------------------------------------------------------------
    |
    | Un producto puede aparecer en muchos carritos de distintos usuarios.
    |
    | Relación en base de datos:
    | cart.product_id -> products.id
    |
    | Esta relación se usará más adelante cuando implementemos el carrito.
    |
    */

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Product tiene muchos Wishlist
    |--------------------------------------------------------------------------
    |
    | Un producto puede estar guardado como favorito por muchos usuarios.
    |
    | Relación en base de datos:
    | wishlists.product_id -> products.id
    |
    */

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Product tiene muchos OrderItem
    |--------------------------------------------------------------------------
    |
    | Un producto puede aparecer en muchos detalles de órdenes.
    |
    | Relación en base de datos:
    | order_items.product_id -> products.id
    |
    | Esto permite consultar en qué compras aparece un producto.
    |
    */

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relación: Product tiene muchas Review
    |--------------------------------------------------------------------------
    |
    | Un producto puede recibir muchas reseñas de distintos usuarios.
    |
    | Relación en base de datos:
    | reviews.product_id -> products.id
    |
    */

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: productos activos
    |--------------------------------------------------------------------------
    |
    | Este scope nos permitirá reutilizar el filtro de productos activos
    | en controladores y consultas del catálogo público.
    |
    | Ejemplo:
    | Product::active()->get();
    |
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
