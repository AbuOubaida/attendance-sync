<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->cascadeOnDelete();
            $table->string('machine_user_id');      // the "Ac-No" / user id stored on the device
            $table->timestamp('punch_time');
            $table->unsignedTinyInteger('verify_mode')->nullable(); // fingerprint/card/password code from device
            $table->unsignedTinyInteger('status_code')->nullable(); // check-in/check-out/etc code from device
            $table->json('raw')->nullable();        // raw record from the SDK, for debugging/future mapping
            $table->boolean('pushed_to_secondary')->default(false);
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            // Prevents the same punch being stored twice if a pull overlaps
            // a previous one (ZK devices happily return the whole buffer).
            $table->unique(['device_id', 'machine_user_id', 'punch_time'], 'attendance_logs_unique_punch');
            $table->index('pushed_to_secondary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
