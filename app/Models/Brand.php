<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
     /*
    |--------------------------------------------------------------------------
    | Modelo: Brand
    |--------------------------------------------------------------------------
    |
    | Este modelo representa la tabla "brands".
    | Una marca puede estar relacionada con muchos productos.
    |
    | Ejemplo:
    | - Apple
    | - Samsung
    | - Xiaomi
    |
    | La tabla brands tiene los campos:
    | id, name, slug, description, created_at, updated_at.
    |
    */

    protected $table = 'brands';

    /*
    |--------------------------------------------------------------------------
    | Campos permitidos para asignación masiva
    |--------------------------------------------------------------------------
    |
    | Estos campos se podrán registrar o actualizar desde controladores,
    | seeders o servicios internos.
    |
    */

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relación: Brand tiene muchos Product
    |--------------------------------------------------------------------------
    |
    | Una marca puede tener muchos productos.
    |
    | Relación en base de datos:
    | products.brand_id -> brands.id
    |
    | Ejemplo:
    | Brand::with('products')->find(1);
    |
    */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
