<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Attendance Logs</h3>
        <a href="{{ route('attendance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Devices</a>
    </div>

    <form class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="device_id" class="form-select form-select-sm">
                <option value="">All devices</option>
                @foreach ($devices as $d)
                    <option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input name="machine_user_id" value="{{ request('machine_user_id') }}" class="form-control form-control-sm" placeholder="Machine User ID / Ac-No">
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-primary">Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr><th>Device</th><th>User ID</th><th>Punch Time</th><th>Verify Mode</th><th>Status</th><th>Pushed</th></tr>
                </thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->device->name ?? '—' }}</td>
                        <td>{{ $log->machine_user_id }}</td>
                        <td>{{ $log->punch_time }}</td>
                        <td>{{ $log->verify_mode }}</td>
                        <td>{{ $log->status_code }}</td>
                        <td>
                            <span class="badge bg-{{ $log->pushed_to_secondary ? 'success' : 'secondary' }}">
                                {{ $log->pushed_to_secondary ? 'yes' : 'pending' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</div>
</body>
</html>
