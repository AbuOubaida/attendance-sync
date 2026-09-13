<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use Exception;

/**
 * Thin wrapper around the coding-libs/zkteco-php package so the rest of the
 * app never talks to the SDK directly. If you install a different ZKTeco
 * PHP library, this is the ONLY file you should need to change — everything
 * else (command, controllers, dashboard) talks to this class, not the SDK.
 *
 * composer require coding-libs/zkteco-php
 * https://packagist.org/packages/coding-libs/zkteco-php  (verified real package, 10k+ installs)
 *
 * CONFIRMED from that package's README: connect(), getAttendances() [plural],
 * getUsers(), disconnect(). Its README does NOT document a disableDevice()/
 * enableDevice() pair, so this wrapper does not call them — if your
 * installed version happens to expose them and you want the extra safety
 * of pausing the device mid-read, check `vendor/coding-libs/zkteco-php`
 * and add the calls back in pullAttendance() below.
 *
 * The README itself flags this library as "not recommended for
 * production" (still under active development) — test thoroughly against
 * your actual MB460 / MB460-ID devices before relying on it for anything
 * business-critical, and keep an eye on its GitHub issues.
 */
class ZKTecoService
{
    protected ZKTeco $zk;
    protected AttendanceDevice $device;

    public function __construct(AttendanceDevice $device)
    {
        $this->device = $device;
        // Signature per README: new ZKTeco(ip, port, shouldPing, timeout, password)
        $this->zk = new ZKTeco($device->ip_address, $device->port, false, 25, $device->comm_key ?: 0);
    }

    public function connect(): bool
    {
        $ok = $this->zk->connect();

        $this->device->forceFill([
            'last_connected_at' => $ok ? now() : $this->device->last_connected_at,
        ])->save();

        if (!$ok) {
            throw new Exception("Could not connect to device [{$this->device->name}] at {$this->device->ip_address}:{$this->device->port}");
        }

        return $ok;
    }

    /**
     * Pull the attendance buffer from the device.
     * Returns a normalized array of ['machine_user_id','punch_time','verify_mode','status_code','raw']
     *
     * IMPORTANT: dd($this->zk->getAttendances()) once against your real
     * device before trusting this mapping — array key names for
     * user id / timestamp / verify type vary across ZK firmware, and this
     * library's README doesn't spell out the exact shape.
     */
    public function pullAttendance(): array
    {
        $rawRecords = $this->zk->getAttendances();

        $normalized = [];
        foreach ($rawRecords as $record) {
            $record = (array) $record;

            $normalized[] = [
                'machine_user_id' => (string) ($record['id'] ?? $record['uid'] ?? $record['user_id'] ?? ''),
                'punch_time'      => $record['timestamp'] ?? $record['time'] ?? $record['record_time'] ?? null,
                'verify_mode'     => $record['type'] ?? $record['verified'] ?? $record['verify_type'] ?? null,
                'status_code'     => $record['state'] ?? $record['status'] ?? null,
                'raw'             => $record,
            ];
        }

        return $normalized;
    }

    public function disconnect(): void
    {
        $this->zk->disconnect();
    }

    /**
     * Quick connectivity check for the dashboard's "Status" column,
     * without pulling attendance data.
     */
    public function ping(): bool
    {
        try {
            $ok = $this->zk->connect();
            $this->zk->disconnect();
            return (bool) $ok;
        } catch (Exception $e) {
            return false;
        }
    }
}
