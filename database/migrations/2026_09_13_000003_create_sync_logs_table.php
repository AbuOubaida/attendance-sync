<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->enum('type', ['pull', 'push']);   // pull = device -> primary DB, push = primary -> secondary DB
            $table->enum('status', ['success', 'failed']);
            $table->unsignedInteger('records_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
