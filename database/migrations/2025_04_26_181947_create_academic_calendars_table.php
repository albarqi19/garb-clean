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
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year'); // السنة الدراسية (مثلاً: 1446 هـ)
            $table->string('name'); // اسم التقويم
            $table->date('start_date'); // تاريخ بداية العام الدراسي
            $table->date('end_date'); // تاريخ نهاية العام الدراسي
            $table->boolean('is_current')->default(false); // هل هو التقويم الحالي
            $table->text('description')->nullable(); // وصف للتقويم
            $table->timestamps();
        });
        
        // جدول الفصول الدراسية
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_calendar_id')->constrained()->onDelete('cascade');
            $table->string('name'); // اسم الفصل (الفصل الدراسي الأول، الثاني، الثالث)
            $table->date('start_date'); // تاريخ بداية الفصل
            $table->date('end_date'); // تاريخ نهاية الفصل
            $table->timestamps();
        });
        
        // جدول الإجازات الرسمية والعطل
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_calendar_id')->constrained()->onDelete('cascade');
            $table->string('name'); // اسم العطلة (إجازة اليوم الوطني، إجازة عيد الفطر، إلخ)
            $table->date('start_date'); // تاريخ بداية العطلة
            $table->date('end_date'); // تاريخ نهاية العطلة
            $table->text('description')->nullable(); // وصف للعطلة
            $table->timestamps();
        });
        
        // جدول للأيام التي لا يوجد فيها دوام لأسباب خاصة
        Schema::create('non_attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_calendar_id')->constrained()->onDelete('cascade');
            $table->date('date'); // التاريخ
            $table->string('reason')->nullable(); // سبب عدم الدوام
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_attendance_days');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('academic_calendars');
    }
};
