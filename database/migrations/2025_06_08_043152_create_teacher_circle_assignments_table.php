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
        Schema::create('teacher_circle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('quran_circle_id')->constrained('quran_circles')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // منع التكليف المكرر للمعلم في نفس الحلقة النشطة
            $table->unique(['teacher_id', 'quran_circle_id', 'is_active'], 'unique_active_assignment');
            
            // فهارس لتحسين الأداء
            $table->index(['teacher_id', 'is_active']);
            $table->index(['quran_circle_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_circle_assignments');
    }
};
