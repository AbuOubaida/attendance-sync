# ZKTeco Attendance Sync — Laravel Module

Pulls attendance punches from your ZKTeco devices (like the "Admin Office" /
"Corporate Office" machines in your Attendance Management Program) into a
MySQL database automatically, pushes them on to a second database, and
exposes everything through a small dashboard + REST API.

## What's in here

```
app/Console/Commands/SyncAttendanceCommand.php   <- the actual sync job
app/Services/ZKTecoService.php                    <- talks to the device
app/Models/{AttendanceDevice,AttendanceLog,SyncLog}.php
app/Http/Controllers/AttendanceDashboardController.php
app/Http/Controllers/Api/AttendanceApiController.php
app/Http/Middleware/VerifyApiKey.php
database/migrations/*.php                         <- tables auto-created by `php artisan migrate`
resources/views/attendance/*.blade.php            <- dashboard UI
routes/{web,api}.php                              <- route snippets to merge in
config/SNIPPETS.md                                <- .env / config/database.php snippets
```

## Install steps

### 1. Copy files into your existing Laravel app
Copy each folder above into the matching folder in your project (same
paths). `routes/web.php` and `routes/api.php` here are snippets to
**merge into** your existing route files, not replace them.

### 2. Install the ZKTeco SDK

```bash
composer require coding-libs/zkteco-php
```

This is a real, verified package (10k+ installs on Packagist) — but its
own README flags it as still under active development, so open
`vendor/coding-libs/zkteco-php/README.md` after installing and confirm
the method names (`connect`, `getAttendances`, `disconnect`) still
match what's used in `app/Services/ZKTecoService.php`. That service
file is the only place you'd need to adjust if a newer version renames
something.

### 3. Set up the primary database (local or remote — just a normal Laravel DB)

Point your existing `.env` `DB_*` variables at whichever MySQL server
you want as the staging DB (can be `127.0.0.1` for local dev, or a
real server's IP/host).

Then run:

```bash
php artisan migrate
```

This creates `attendance_devices`, `attendance_logs`, and `sync_logs`
automatically — no manual SQL needed for the primary DB.

### 4. Set up the secondary database

Follow `config/SNIPPETS.md` sections 1–2 (env vars + the `secondary`
connection in `config/database.php`), then run the one CREATE TABLE
statement in section 5 against that second server.

### 5. Wire up the API key + middleware + schedule

Follow `config/SNIPPETS.md` sections 3, 4, and 6.

### 6. Register your devices

Either via the dashboard's "Add Device" form at `/attendance`, or
directly:

```php
App\Models\AttendanceDevice::create([
    'name' => 'Admin Office',
    'ip_address' => '192.168.**.**',
    'port' => 4370,
    'status' => 'active',
]);

App\Models\AttendanceDevice::create([
    'name' => 'Corporate Office',
    'ip_address' => '192.168.**.**',
    'port' => 4370,
    'status' => 'active',
]);
```

(Matches the two machines shown in your screenshot.)

### 7. Run a sync manually to test

```bash
php artisan attendance:sync
# or a single device:
php artisan attendance:sync 1
```

Check `/attendance` (dashboard), `/attendance/logs`, and
`/attendance/sync-history` to confirm records landed.

### 8. Let it run automatically

The scheduler line in `config/SNIPPETS.md` #6 runs `attendance:sync`
every 5 minutes once your server's real cron calls
`php artisan schedule:run` every minute. Adjust the frequency
(`->everyMinute()`, `->hourly()`, etc.) to match how urgent your
attendance data needs to be.

## Using the API

```bash
curl -H "X-API-KEY: <ATTENDANCE_API_KEY from .env>" \
  https://your-app.test/api/attendance/devices

curl -H "X-API-KEY: <key>" \
  "https://your-app.test/api/attendance/logs?device_id=1&from=2026-09-01"

curl -X POST -H "X-API-KEY: <key>" \
  https://your-app.test/api/attendance/sync/1
```

## Notes / things to double check on your hardware

- Your screenshot shows one device as `MB460` and another as
  `MB460/ID` (an ID-card variant) — attendance record fields can
  differ slightly between fingerprint-only and ID-card firmware.
  If `pullAttendance()` returns unexpected keys for one device,
  `dd($rawRecords)` inside `ZKTecoService::pullAttendance()`
  temporarily to see the actual array shape from your SDK version,
  then adjust the key mapping.
- Disabling the device during a pull (as the service does) is
  standard practice to avoid a half-read buffer, but it briefly
  blocks new punches at the terminal — keep sync intervals short
  enough that this isn't noticeable, or remove that call if your
  firmware handles concurrent reads fine.
- The unique constraint on `(device_id, machine_user_id, punch_time)`
  is what makes repeated pulls safe — the same buffer can be read
  twice without duplicating rows.
