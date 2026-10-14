## Creación del proyecto

```sh
laravel new ecommerce-api --database=pgsql --no-authentication --pest --no-interaction
```

- `laravel new name_project`: Crear el proyecto
- `--database=pgsql`: Configurar PostgreSQL
- `--api`: Configuración de rutas de API
- `--no-authentication`: No usar un started kit durante la instalación
- `--pest`: Preparar pruebas con Pest
- `--no-interaction`: Evitar preguntas durante la instalación


Configurar en `.env`:
```sh
# guarda las sesiones en la base de datos
# para evitar crear la tabla de sessions
SESSION_DRIVER=file

# Despues utilizar
php artisan config:clear
```

## Instalación de autentificación

```sh
# Ayuda a autentificar usuarios en APIs, emitir tokens
composer require laravel/sanctum
```

## Creación de los modelos

Empecemos con la creación de modelos y relaciones, añade comentarios detallados dentro de cada modelo, tambien los comandos necesarios para crear estos, vamos paso a paso y no pasemos al siguiente sin que te confime.

```sh
php artisan make:model Category
php artisan make:model Brand
php artisan make:model Product
php artisan make:model Cart
php artisan make:model Wishlist
php artisan make:model ShippingAddress
php artisan make:model Order
php artisan make:model OrderItem
php artisan make:model Payment
php artisan make:model Review
```

## Pasos iniciales

```sh
# Conocer las tablas de mi base de datos
php artisan db:show

# Utilizar tinker para consultar con la base de datos
php artisan tinker
> App\Models\Category::count();
> App\Models\Brand::count();
> App\Models\Product::with(['category', 'brand'])->first();
> App\Models\User::count();
> App\Models\User::with(['cartItems.product'])->first();
> App\Models\User::with(['wishlists.product'])->first();
> App\Models\Cart::with(['user', 'product'])->first();
> App\Models\Wishlist::with(['user', 'product'])->first();
> App\Models\User::customers()->count();
> App\Models\User::admins()->count();
> App\Models\User::with(['shippingAddresses', 'orders.items.product', 'reviews.product'])->first();

> exit ó q

# Saber el estado de las migraciones
php artisan migrate:status
```
