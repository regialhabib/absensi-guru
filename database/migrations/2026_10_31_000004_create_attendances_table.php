<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->dateTime('check_in_at');
            $table->decimal('check_in_latitude', 10, 7);
            $table->decimal('check_in_longitude', 10, 7);
            $table->decimal('check_in_accuracy', 8, 2);
            $table->decimal('check_in_distance', 8, 2);
            $table->string('check_in_photo');
            $table->boolean('check_in_face_detected');
            $table->string('status');
            $table->unsignedInteger('late_minutes')->default(0);

            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->decimal('check_out_distance', 8, 2)->nullable();
            $table->string('check_out_photo')->nullable();
            $table->boolean('check_out_face_detected')->nullable();
            $table->unsignedInteger('early_leave_minutes')->default(0);

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
