<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    protected $fillable = [
        'name', 'ip_address', 'port', 'comm_key', 'serial_number',
        'product_name', 'status', 'last_connected_at', 'last_sync_at',
        'last_sync_status', 'last_error',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
        'last_sync_at'      => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'device_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
