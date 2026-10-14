<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Modelo: Category
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "categories".
    | Una categoría agrupa productos dentro del ecommerce.
    |
    | Ejemplo:
    | - Laptops
    | - Celulares
    | - Accesorios
    |
    | La tabla categories tiene los campos:
    | id, name, slug, description, created_at, updated_at.
    |
    */

    protected $table = 'categories';

    /*
    |--------------------------------------------------------------------------
    | Campos asignables masivamente
    |--------------------------------------------------------------------------
    |
    | Estos campos podrán ser usados con create(), update() o fill().
    | No incluimos "id" porque es autoincremental.
    |
    */

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Category tiene muchos Product
    |--------------------------------------------------------------------------
    |
    | Una categoría puede tener muchos productos.
    |
    | Relación en base de datos:
    | products.category_id -> categories.id
    |
    | Ejemplo:
    | Category::with('products')->find(1);
    |
    */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
