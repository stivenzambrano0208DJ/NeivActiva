# NeivActiva

Aplicacion web PHP para gestion de eventos, inscripciones, asistencia por QR y certificados digitales. El proyecto usa una arquitectura MVC ligera con controladores, modelos, servicios y vistas separadas.

## Requisitos

- PHP 8.1 o superior
- MySQL/MariaDB
- Composer
- Apache con `mod_rewrite` habilitado
- XAMPP en desarrollo local

## Instalacion local

1. Instala dependencias:

```bash
composer install
```

2. Copia el archivo de entorno:

```bash
copy .env.example .env
```

3. Configura `.env` con tus credenciales:

```env
DB_HOST=localhost
DB_NAME=neivactiva_db
DB_USER=root
DB_PASS=
APP_URL=http://localhost/NeivActiva
APP_ENV=development
```

4. Crea la base de datos e importa `database/schema.sql` desde phpMyAdmin o la consola MySQL.

5. Accede a la aplicacion:

```text
http://localhost/NeivActiva/public/
```

## Instalador web

`public/setup.php` esta deshabilitado por defecto porque ejecuta operaciones criticas de base de datos. Si necesitas usarlo temporalmente en desarrollo:

1. Define en `.env`:

```env
WEB_SETUP_ENABLED=true
WEB_SETUP_TOKEN=un-token-largo-y-aleatorio
```

2. Abre:

```text
http://localhost/NeivActiva/public/setup.php?token=un-token-largo-y-aleatorio
```

3. Vuelve a dejar `WEB_SETUP_ENABLED=false` al terminar.

## Seguridad

- Las contrasenas nuevas se guardan con `password_hash`.
- Las contrasenas antiguas en texto claro se migran automaticamente a hash tras un inicio de sesion exitoso.
- Las consultas de aplicacion usan consultas preparadas por medio de PDO.
- Las acciones sensibles usan tokens CSRF.
- Las sesiones usan cookies `HttpOnly`, `SameSite=Lax` y `Secure` cuando se sirve por HTTPS.
- Apache bloquea acceso directo a `.env`, `app/`, `config/`, `database/`, `resources/`, `vendor/` y otros archivos internos.
- En produccion usa `APP_ENV=production`, HTTPS y credenciales de base de datos sin privilegios administrativos.

## Estructura

- `app/Core`: router, base de datos, autenticacion, CSRF y utilidades.
- `app/Controllers`: controladores HTTP.
- `app/Models`: acceso a datos.
- `app/Services`: servicios de dominio, correo, QR y estadisticas.
- `resources/views`: vistas PHP.
- `public`: front controller, assets y archivos publicos.
- `database`: schema y migraciones.

## Despliegue

1. Ejecuta `composer install --no-dev --optimize-autoloader`.
2. Configura `.env` con valores de produccion.
3. Apunta el document root del servidor a `public/` cuando sea posible.
4. Asegura HTTPS.
5. Revisa permisos de `public/uploads`.
6. Mantén `WEB_SETUP_ENABLED=false`.

## Contribucion

- Mantener controladores delgados y mover reglas de negocio a servicios/modelos.
- Usar consultas preparadas para todo dato externo.
- Agregar token CSRF a todo formulario `POST`.
- No guardar secretos en el repositorio.
- Validar sintaxis PHP antes de enviar cambios:

```bash
php -l ruta/al/archivo.php
```
