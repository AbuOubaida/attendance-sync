<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'device_id', 'machine_user_id', 'punch_time', 'verify_mode',
        'status_code', 'raw', 'pushed_to_secondary', 'pushed_at',
    ];

    protected $casts = [
        'punch_time'           => 'datetime',
        'raw'                  => 'array',
        'pushed_to_secondary'  => 'boolean',
        'pushed_at'            => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function scopeUnpushed($query)
    {
        return $query->where('pushed_to_secondary', false);
    }
}
