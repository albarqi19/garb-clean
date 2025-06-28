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
        Schema::create('marketing_kpis', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المؤشر
            $table->text('description')->nullable(); // وصف المؤشر
            $table->string('unit'); // وحدة القياس (عدد، نسبة مئوية، مبلغ مالي، إلخ)
            $table->enum('frequency', ['شهري', 'ربعي', 'سنوي'])->default('شهري'); // دورية المؤشر
            $table->enum('calculation_type', ['تراكمي', 'متوسط', 'آخر قيمة'])->default('تراكمي'); // طريقة احتساب المؤشر
            $table->float('weight')->default(1.0); // وزن المؤشر (أهميته النسبية)
            $table->float('target_value')->nullable(); // القيمة المستهدفة
            $table->boolean('is_active')->default(true); // حالة المؤشر (نشط/غير نشط)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_kpis');
    }
};