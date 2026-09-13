<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class AttendanceDashboardController extends Controller
{
    public function index()
    {
        $devices = AttendanceDevice::withCount('logs')->orderBy('name')->get();
        return view('attendance.dashboard', compact('devices'));
    }

    public function storeDevice(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port'       => ['required', 'integer'],
            'comm_key'   => ['nullable', 'string'],
        ])->validate();

        AttendanceDevice::create($data + ['status' => 'active']);

        return back()->with('status', 'Device added.');
    }

    public function syncNow(?AttendanceDevice $device = null)
    {
        Artisan::call('attendance:sync', $device ? ['device' => $device->id] : []);
        return back()->with('status', 'Sync triggered: ' . Artisan::output());
    }

    public function logs(Request $request)
    {
        $logs = AttendanceLog::with('device:id,name')
            ->when($request->device_id, fn ($q) => $q->where('device_id', $request->device_id))
            ->when($request->machine_user_id, fn ($q) => $q->where('machine_user_id', $request->machine_user_id))
            ->orderByDesc('punch_time')
            ->paginate(50);

        $devices = AttendanceDevice::orderBy('name')->get(['id', 'name']);

        return view('attendance.logs', compact('logs', 'devices'));
    }

    public function syncHistory()
    {
        $history = SyncLog::with('device:id,name')->orderByDesc('created_at')->paginate(50);
        return view('attendance.sync-history', compact('history'));
    }
}
