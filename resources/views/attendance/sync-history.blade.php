<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sync History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Sync History</h3>
        <a href="{{ route('attendance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Devices</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr><th>When</th><th>Device</th><th>Type</th><th>Status</th><th>Records</th><th>Message</th></tr>
                </thead>
                <tbody>
                @foreach ($history as $h)
                    <tr>
                        <td>{{ $h->created_at }}</td>
                        <td>{{ $h->device->name ?? 'ALL' }}</td>
                        <td>{{ $h->type }}</td>
                        <td>
                            <span class="badge bg-{{ $h->status === 'success' ? 'success' : 'danger' }}">{{ $h->status }}</span>
                        </td>
                        <td>{{ $h->records_count }}</td>
                        <td class="small">{{ $h->message }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $history->links() }}
        </div>
    </div>
</div>
</body>
</html>
