<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // "Acme Corp", "Internal Dashboard", etc.
            $table->string('key_prefix', 12);        // first chars of the key, shown in the UI for identification
            $table->string('key_hash');              // sha256 of the full key — the raw key is NEVER stored
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip')->nullable();
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
