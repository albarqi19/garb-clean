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
        Schema::create('teacher_mosque_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('mosque_id')->constrained('mosques')->onDelete('cascade');
            $table->string('day_of_week'); // اليوم (الأحد، الاثنين، إلخ)
            $table->time('start_time'); // وقت البداية
            $table->time('end_time'); // وقت النهاية
            $table->string('session_type')->default('حلقة'); // نوع الجلسة (حلقة، درس، مراجعة)
            $table->text('notes')->nullable(); // ملاحظات
            $table->boolean('is_active')->default(true); // حالة النشاط
            $table->timestamps();
            
            // فهرس مركب لمنع التضارب في الأوقات لنفس المعلم
            $table->unique(['teacher_id', 'day_of_week', 'start_time'], 'teacher_schedule_unique');
            
            // فهارس للبحث السريع
            $table->index(['teacher_id', 'day_of_week']);
            $table->index(['mosque_id', 'day_of_week']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_mosque_schedules');
    }
};
