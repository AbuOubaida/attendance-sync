<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = ['device_id', 'type', 'status', 'records_count', 'message'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
}
