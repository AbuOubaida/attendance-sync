# Config snippets — merge these into your existing app

## 1. .env — add these lines

Your existing DB_* variables become the PRIMARY (staging) database —
the one the sync command pulls attendance into first, whether it's
your local dev MySQL or a real server.

```env
# --- Secondary / target DB (local or remote) ---
SECONDARY_DB_CONNECTION=mysql
SECONDARY_DB_HOST=127.0.0.1
SECONDARY_DB_PORT=3306
SECONDARY_DB_DATABASE=attendance_secondary
SECONDARY_DB_USERNAME=root
SECONDARY_DB_PASSWORD=
```

Note: API auth no longer uses a single `.env` key. See section 3 —
keys are now issued per client via the `api_clients` table and the
`/attendance/api-clients` dashboard page or `php artisan api-client:create`.

## 2. config/database.php — add a 'secondary' connection

Inside the 'connections' array, alongside your existing 'mysql' entry:

```php
'secondary' => [
    'driver'    => 'mysql',
    'host'      => env('SECONDARY_DB_HOST', '127.0.0.1'),
    'port'      => env('SECONDARY_DB_PORT', '3306'),
    'database'  => env('SECONDARY_DB_DATABASE', 'forge'),
    'username'  => env('SECONDARY_DB_USERNAME', 'forge'),
    'password'  => env('SECONDARY_DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'strict'    => true,
    'engine'    => null,
],
```

## 3. API keys — per client, issued and revokable

Run the `create_api_clients_table` migration (included in
`database/migrations/`) and you're done on the config side — no
`.env` key needed. Issue keys either:

- **Via the dashboard**: visit `/attendance/api-clients`, enter a
  client name, click Generate. The raw key is shown ONCE — copy it
  immediately, it is never stored or retrievable again (only its
  sha256 hash is kept in the DB).
- **Via the terminal**: `php artisan api-client:create "Acme Corp"`

Revoke a key any time from the same dashboard page — revoked keys
are rejected immediately by the middleware, no cache to clear.

## 4. app/Http/Kernel.php — register the middleware alias

Laravel 11+ uses bootstrap/app.php instead — see note below.

```php
// $middlewareAliases (or $routeMiddleware on Laravel 9/10)
'verify.api.key' => \App\Http\Middleware\VerifyApiKey::class,
```

For Laravel 11+, register it in `bootstrap/app.php` instead:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['verify.api.key' => \App\Http\Middleware\VerifyApiKey::class]);
})
```

## 5. Secondary DB schema

The secondary database needs its own `attendance_logs` table (kept
simple/denormalized since it doesn't need to know about your devices
table). Run this against the SECONDARY database:

```sql
CREATE TABLE attendance_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id BIGINT UNSIGNED NOT NULL,   -- id of the row in the primary DB
    device_name VARCHAR(255) NULL,
    device_ip VARCHAR(45) NULL,
    machine_user_id VARCHAR(50) NOT NULL,
    punch_time DATETIME NOT NULL,
    verify_mode TINYINT UNSIGNED NULL,
    status_code TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_source (source_id)
);
```

(The primary DB's `attendance_devices`, `attendance_logs`, and
`sync_logs` tables are created automatically by the migrations in
`database/migrations/` — no manual SQL needed there.)

## 6. Register the artisan command's schedule

In `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` (older):

```php
Schedule::command('attendance:sync')->everyFiveMinutes()->withoutOverlapping();
```

Then make sure the Laravel scheduler is actually running via your
server's real cron (one line, once):

```
* * * * * cd /path-to-your-app && php artisan schedule:run >> /dev/null 2>&1
```
