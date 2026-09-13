<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiClient extends Model
{
    protected $fillable = [
        'name', 'key_prefix', 'key_hash', 'status',
        'last_used_at', 'last_used_ip', 'request_count',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Creates a new client and returns [ApiClient $client, string $rawKey].
     * The raw key is ONLY available here, at creation time — it is never
     * stored anywhere and cannot be retrieved again later. If it's lost,
     * revoke this client and issue a new one.
     */
    public static function generate(string $name): array
    {
        $rawKey = 'zk_' . Str::random(40); // prefix makes leaked keys greppable in logs

        $client = static::create([
            'name'       => $name,
            'key_prefix' => substr($rawKey, 0, 10),
            'key_hash'   => hash('sha256', $rawKey),
            'status'     => 'active',
        ]);

        return [$client, $rawKey];
    }

    public static function findByRawKey(string $rawKey): ?self
    {
        return static::where('key_hash', hash('sha256', $rawKey))
            ->where('status', 'active')
            ->first();
    }

    public function recordUsage(?string $ip): void
    {
        $this->increment('request_count');
        $this->forceFill(['last_used_at' => now(), 'last_used_ip' => $ip])->save();
    }
}
