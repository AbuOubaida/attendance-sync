<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class CreateApiClientCommand extends Command
{
    protected $signature = 'api-client:create {name : A label for who/what this key is for}';
    protected $description = 'Issue a new API key for a client. The raw key is shown ONCE — save it immediately.';

    public function handle(): int
    {
        [$client, $rawKey] = ApiClient::generate($this->argument('name'));

        $this->newLine();
        $this->info("Client '{$client->name}' created (id: {$client->id}).");
        $this->warn('API KEY (shown once — copy it now, it cannot be retrieved again):');
        $this->line($rawKey);
        $this->newLine();
        $this->line('Give this to the client to send as header: X-API-KEY: ' . $rawKey);

        return self::SUCCESS;
    }
}
