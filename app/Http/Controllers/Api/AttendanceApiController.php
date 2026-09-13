<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AttendanceApiController extends Controller
{
    // GET /api/devices
    public function devices()
    {
        return AttendanceDevice::select(
            'id', 'name', 'ip_address', 'port', 'product_name', 'status',
            'last_connected_at', 'last_sync_at', 'last_sync_status'
        )->get();
    }

    // GET /api/attendance-logs?device_id=&machine_user_id=&from=&to=&per_page=
    public function logs(Request $request)
    {
        $query = AttendanceLog::with('device:id,name,ip_address')
            ->when($request->device_id, fn ($q) => $q->where('device_id', $request->device_id))
            ->when($request->machine_user_id, fn ($q) => $q->where('machine_user_id', $request->machine_user_id))
            ->when($request->from, fn ($q) => $q->where('punch_time', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('punch_time', '<=', $request->to))
            ->orderByDesc('punch_time');

        return $query->paginate($request->integer('per_page', 50));
    }

    // POST /api/sync/{device?}
    public function syncNow(?int $device = null)
    {
        Artisan::call('attendance:sync', $device ? ['device' => $device] : []);

        return response()->json([
            'message' => 'Sync triggered',
            'output'  => Artisan::output(),
        ]);
    }
}
