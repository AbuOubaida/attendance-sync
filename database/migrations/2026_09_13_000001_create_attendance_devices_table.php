<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table is created automatically the first time you run
        // `php artisan migrate` — no manual DB setup needed beyond
        // pointing .env at your MySQL server (local or remote).
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Admin Office", "Corporate Office"
            $table->string('ip_address');
            $table->unsignedInteger('port')->default(4370);
            $table->string('comm_key')->nullable();        // device comm password, if set
            $table->string('serial_number')->nullable();
            $table->string('product_name')->nullable();    // MB460, MB460/ID, etc
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status')->nullable(); // success / failed
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
