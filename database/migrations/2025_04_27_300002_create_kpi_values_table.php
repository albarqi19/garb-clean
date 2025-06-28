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
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('marketing_kpis')->onDelete('cascade');
            $table->date('period_start_date'); // تاريخ بداية الفترة
            $table->date('period_end_date'); // تاريخ نهاية الفترة
            $table->string('period_label'); // وصف الفترة (مثال: "أبريل 2025" أو "الربع الثاني 2025")
            $table->float('actual_value'); // القيمة الفعلية المحققة
            $table->float('target_value'); // القيمة المستهدفة للفترة
            $table->float('achievement_percentage')->nullable(); // نسبة تحقيق المستهدف
            $table->text('notes')->nullable(); // ملاحظات
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // المستخدم الذي سجل القيمة
            $table->timestamps();
            
            // تأكد من عدم تكرار نفس المؤشر لنفس الفترة
            $table->unique(['kpi_id', 'period_start_date', 'period_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_values');
    }
};