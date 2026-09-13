<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiClientController extends Controller
{
    public function index()
    {
        $clients = ApiClient::orderByDesc('created_at')->get();
        return view('attendance.api-clients', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        [$client, $rawKey] = ApiClient::generate($data['name']);

        // Flashed once — the view shows it in a copyable box, then it's gone
        // from session on the next request. It's never stored anywhere.
        return back()->with('new_api_key', $rawKey)->with('new_api_key_name', $client->name);
    }

    public function revoke(ApiClient $client)
    {
        $client->update(['status' => 'revoked']);
        return back()->with('status', "Key for '{$client->name}' revoked.");
    }
}
