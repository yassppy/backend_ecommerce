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

## Registro con PayPal

1. Regístrate como Personal (no es necesario añadir número de tarjeta):
   [https://www.paypal.com/webapps/mpp/account-selection?intent=developer&country.x=PE&locale.x=es_XC](https://www.paypal.com/webapps/mpp/account-selection?intent=developer&country.x=PE&locale.x=es_XC)

2. Accede al panel de desarrolladores:
   [https://developer.paypal.com/dashboard/](https://developer.paypal.com/dashboard/)

3. Ve a **Apps & Credentials** → **Create App**, asigna un nombre y deja el entorno en **Sandbox**.

4. Para probar el flujo de pago:
   - Ve a **Testing Tools** → **Sandbox Accounts**.
   - Selecciona la cuenta de tipo **Personal** y copia sus credenciales (`...@personal.example.com` / contraseña).
   - Ejecuta el endpoint `POST /api/v1/payments/paypal/create/{order_id}` y copia la `approval_url` devuelta.
   - Abre la `approval_url` en una ventana de incógnito e inicia sesión con las credenciales de la cuenta Personal de Sandbox.
   - Aprueba el pago; PayPal redirigirá automáticamente al endpoint `/success` y la orden quedará marcada como **paid**.

---

## Configuración de Neon (base de datos)

Al copiar la cadena de conexión desde el panel de Neon, **elimina el segmento `-pooler`** del host antes de pegarla en el `.env`:

```env
DB_CONNECTION=pgsql
DB_URL="postgresql://neondb_owner:TU_PASSWORD@ep-XXXX.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
```

> **Nota:** El host debe ser `ep-XXXX.us-east-2.aws.neon.tech`, **sin** `-pooler`.

### Solución a problemas de conexión con IPv6

Si la conexión falla, deshabilita IPv6 en tu adaptador de red:

1. Abre **Centro de redes y recursos compartidos** → **Cambiar configuración del adaptador**.
2. Haz clic derecho en tu Wi-Fi → **Propiedades**.
3. Desmarca **Protocolo de Internet versión 6 (TCP/IPv6)** → **Aceptar**.

### Limpiar caché de configuración

Después de modificar el `.env`, ejecuta:

```bash
php artisan config:clear
```