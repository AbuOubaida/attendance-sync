<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>API Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>API Clients</h3>
        <a href="{{ route('attendance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Devices</a>
    </div>

    @if (session('status'))
        <div class="alert alert-info small">{{ session('status') }}</div>
    @endif

    @if (session('new_api_key'))
        <div class="alert alert-warning">
            <strong>New key for "{{ session('new_api_key_name') }}" — copy it now, it will not be shown again:</strong>
            <div class="input-group mt-2">
                <input type="text" class="form-control font-monospace" id="newKeyBox" value="{{ session('new_api_key') }}" readonly>
                <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('newKeyBox').value)">Copy</button>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Issue New Key</div>
        <div class="card-body">
            <form action="{{ route('attendance.api-clients.store') }}" method="post" class="row g-2">
                @csrf
                <div class="col-md-6">
                    <input name="name" class="form-control form-control-sm" placeholder="Client name (e.g. Acme Corp, Internal App)" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-success w-100">Generate Key</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm align-middle">
                <thead>
                <tr><th>Name</th><th>Key Prefix</th><th>Status</th><th>Requests</th><th>Last Used</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td><code>{{ $client->key_prefix }}...</code></td>
                        <td>
                            <span class="badge bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $client->status }}
                            </span>
                        </td>
                        <td>{{ $client->request_count }}</td>
                        <td>{{ $client->last_used_at?->diffForHumans() ?? 'never' }}</td>
                        <td>
                            @if ($client->status === 'active')
                                <form action="{{ route('attendance.api-clients.revoke', $client->id) }}" method="post" onsubmit="return confirm('Revoke this key? This cannot be undone.')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
