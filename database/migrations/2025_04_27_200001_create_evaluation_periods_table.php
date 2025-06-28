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
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم فترة التقييم (مثل "الفترة الأولى 1446هـ")
            $table->date('start_date'); // تاريخ بداية فترة التقييم
            $table->date('end_date'); // تاريخ نهاية فترة التقييم
            $table->text('description')->nullable(); // وصف فترة التقييم
            $table->enum('status', ['قادمة', 'جارية', 'منتهية'])->default('قادمة'); // حالة فترة التقييم
            $table->boolean('is_active')->default(true); // هل فترة التقييم نشطة؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};