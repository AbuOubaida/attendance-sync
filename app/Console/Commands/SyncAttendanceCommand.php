<?php

namespace App\Console\Commands;

use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\SyncLog;
use App\Services\ZKTecoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SyncAttendanceCommand extends Command
{
    // php artisan attendance:sync            -> all active devices
    // php artisan attendance:sync 3          -> only device id 3
    protected $signature = 'attendance:sync {device? : Specific attendance_devices.id to sync}';
    protected $description = 'Pull attendance logs from ZKTeco device(s) into the primary DB, then push new records to the secondary DB';

    public function handle(): int
    {
        $devices = $this->argument('device')
            ? AttendanceDevice::where('id', $this->argument('device'))->get()
            : AttendanceDevice::active()->get();

        if ($devices->isEmpty()) {
            $this->warn('No active devices found.');
            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $this->pullFromDevice($device);
        }

        $this->pushToSecondary();

        return self::SUCCESS;
    }

    protected function pullFromDevice(AttendanceDevice $device): void
    {
        $this->info("Pulling from [{$device->name}] {$device->ip_address}...");

        try {
            $service = new ZKTecoService($device);
            $service->connect();
            $records = $service->pullAttendance();
            $service->disconnect();

            $inserted = 0;
            foreach ($records as $record) {
                if (empty($record['punch_time']) || empty($record['machine_user_id'])) {
                    continue;
                }

                $created = AttendanceLog::firstOrCreate(
                    [
                        'device_id'       => $device->id,
                        'machine_user_id' => $record['machine_user_id'],
                        'punch_time'      => Carbon::parse($record['punch_time']),
                    ],
                    [
                        'verify_mode' => $record['verify_mode'] ?? null,
                        'status_code' => $record['status_code'] ?? null,
                        'raw'         => $record['raw'] ?? null,
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $inserted++;
                }
            }

            $device->forceFill([
                'last_sync_at'      => now(),
                'last_sync_status'  => 'success',
                'last_error'        => null,
            ])->save();

            SyncLog::create([
                'device_id'     => $device->id,
                'type'          => 'pull',
                'status'        => 'success',
                'records_count' => $inserted,
                'message'       => "{$inserted} new record(s) out of " . count($records) . " read from device buffer.",
            ]);

            $this->info("  -> {$inserted} new record(s) stored.");

        } catch (Exception $e) {
            $device->forceFill([
                'last_sync_status' => 'failed',
                'last_error'       => $e->getMessage(),
            ])->save();

            SyncLog::create([
                'device_id'     => $device->id,
                'type'          => 'pull',
                'status'        => 'failed',
                'records_count' => 0,
                'message'       => $e->getMessage(),
            ]);

            $this->error("  -> Failed: {$e->getMessage()}");
        }
    }

    /**
     * Pushes unsynced rows from the PRIMARY (staging) database into the
     * SECONDARY connection defined in config/database.php as 'secondary'.
     * This lets the primary DB sit local/on-site while the secondary can be
     * a remote/production server, or vice versa.
     */
    protected function pushToSecondary(): void
    {
        $batch = AttendanceLog::with('device')->unpushed()->orderBy('id')->limit(500)->get();

        if ($batch->isEmpty()) {
            return;
        }

        $rows = $batch->map(fn ($log) => [
            'source_id'       => $log->id, // reference back to the primary DB row
            'device_name'     => $log->device->name ?? null,
            'device_ip'       => $log->device->ip_address ?? null,
            'machine_user_id' => $log->machine_user_id,
            'punch_time'      => $log->punch_time,
            'verify_mode'     => $log->verify_mode,
            'status_code'     => $log->status_code,
            'created_at'      => now(),
            'updated_at'      => now(),
        ])->toArray();

        try {
            DB::connection('secondary')->table('attendance_logs')->insertOrIgnore($rows);

            AttendanceLog::whereIn('id', $batch->pluck('id'))->update([
                'pushed_to_secondary' => true,
                'pushed_at'           => now(),
            ]);

            SyncLog::create([
                'device_id'     => null,
                'type'          => 'push',
                'status'        => 'success',
                'records_count' => count($rows),
                'message'       => count($rows) . ' record(s) pushed to secondary DB.',
            ]);

            $this->info(count($rows) . ' record(s) pushed to secondary DB.');

        } catch (Exception $e) {
            SyncLog::create([
                'device_id'     => null,
                'type'          => 'push',
                'status'        => 'failed',
                'records_count' => 0,
                'message'       => $e->getMessage(),
            ]);

            $this->error('Push to secondary DB failed: ' . $e->getMessage());
        }
    }
}
