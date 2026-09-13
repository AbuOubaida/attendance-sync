<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Attendance Devices</h3>
        <div>
            <a href="{{ route('attendance.logs') }}" class="btn btn-outline-secondary btn-sm">Logs</a>
            <a href="{{ route('attendance.sync-history') }}" class="btn btn-outline-secondary btn-sm">Sync History</a>
            <a href="{{ route('attendance.api-clients.index') }}" class="btn btn-outline-secondary btn-sm">API Clients</a>
            <form action="{{ route('attendance.sync') }}" method="post" class="d-inline">
                @csrf
                <button class="btn btn-primary btn-sm">Sync All Now</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-info small">{{ session('status') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>Name</th><th>IP</th><th>Port</th><th>Product</th>
                    <th>Status</th><th>Last Sync</th><th>Result</th><th>Logs</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($devices as $device)
                    <tr>
                        <td>{{ $device->name }}</td>
                        <td>{{ $device->ip_address }}</td>
                        <td>{{ $device->port }}</td>
                        <td>{{ $device->product_name }}</td>
                        <td>
                            <span class="badge bg-{{ $device->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $device->status }}
                            </span>
                        </td>
                        <td>{{ $device->last_sync_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            @if ($device->last_sync_status)
                                <span class="badge bg-{{ $device->last_sync_status === 'success' ? 'success' : 'danger' }}">
                                    {{ $device->last_sync_status }}
                                </span>
                                @if ($device->last_error)
                                    <div class="text-danger small">{{ $device->last_error }}</div>
                                @endif
                            @endif
                        </td>
                        <td>{{ $device->logs_count }}</td>
                        <td>
                            <form action="{{ route('attendance.sync', $device->id) }}" method="post">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary">Sync</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Add Device</div>
        <div class="card-body">
            <form action="{{ route('attendance.devices.store') }}" method="post" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <input name="name" class="form-control form-control-sm" placeholder="Name (e.g. Admin Office)" required>
                </div>
                <div class="col-md-3">
                    <input name="ip_address" class="form-control form-control-sm" placeholder="IP Address" required>
                </div>
                <div class="col-md-2">
                    <input name="port" class="form-control form-control-sm" value="4370" required>
                </div>
                <div class="col-md-2">
                    <input name="comm_key" class="form-control form-control-sm" placeholder="Comm key (optional)">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-success w-100">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
