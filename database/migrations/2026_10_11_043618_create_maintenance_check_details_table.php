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
        Schema::create('maintenance_check_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_item_id')->constrained()->restrictOnDelete();
            $table->string('result_status', 30)->default('belum_diperiksa');
            $table->string('actual_value')->nullable();
            $table->text('note')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->unique(['maintenance_check_id', 'maintenance_item_id'], 'maintenance_check_details_check_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_check_details');
    }
};
