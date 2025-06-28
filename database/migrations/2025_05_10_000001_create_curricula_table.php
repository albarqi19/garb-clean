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
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المنهج
            $table->enum('type', ['منهج تلقين', 'منهج طالب']); // نوع المنهج
            $table->text('description')->nullable(); // وصف المنهج
            $table->boolean('is_active')->default(true); // هل المنهج فعال
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
