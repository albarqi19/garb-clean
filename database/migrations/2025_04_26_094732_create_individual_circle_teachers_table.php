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
        Schema::create('individual_circle_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained('quran_circles')->onDelete('cascade');
            $table->string('name'); // اسم المعلم للحلقة الفردية
            $table->string('phone')->nullable(); // رقم جوال المعلم
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual_circle_teachers');
    }
};
