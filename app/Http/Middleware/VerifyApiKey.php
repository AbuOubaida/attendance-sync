<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;

/**
 * Validates the X-API-KEY header against issued client keys in the
 * api_clients table (see App\Models\ApiClient), instead of a single
 * shared secret. Each client you create via the dashboard or
 * `php artisan api-client:create` gets its own key that can be
 * individually revoked and whose usage is tracked.
 *
 * On success, the matched client is attached to the request so
 * controllers can access it via $request->apiClient if needed
 * (e.g. to scope data per client later).
 */
class VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $rawKey = $request->header('X-API-KEY');

        if (!$rawKey) {
            return response()->json(['message' => 'Missing X-API-KEY header'], 401);
        }

        $client = ApiClient::findByRawKey($rawKey);

        if (!$client) {
            return response()->json(['message' => 'Invalid or revoked API key'], 401);
        }

        $client->recordUsage($request->ip());
        $request->attributes->set('apiClient', $client);

        return $next($request);
    }
}
