<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_schedule_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('check_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('overall_status', 20)->default('incomplete');
            $table->string('verification_status', 20)->default('waiting');
            $table->text('general_note')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_checks');
    }
};
