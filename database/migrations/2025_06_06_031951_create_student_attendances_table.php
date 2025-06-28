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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade'); // الطالب
            $table->date('date'); // تاريخ الحضور
            $table->enum('status', ['حاضر', 'غائب', 'متأخر', 'مأذون']); // حالة الحضور
            $table->string('period')->nullable(); // الفترة (إختيارية)
            $table->text('excuse_reason')->nullable(); // سبب الإعتذار/الغياب
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->string('recorded_by')->nullable(); // من سجل الحضور (API أو مستخدم)
            $table->timestamps();
            
            // فهرس مركب لتجنب التكرار
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
