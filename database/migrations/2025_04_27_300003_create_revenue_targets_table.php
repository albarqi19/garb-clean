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
        Schema::create('revenue_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revenue_type_id')->constrained(); // نوع الإيراد (متجر إلكتروني، مؤسسات، تحويل بنكي، إلخ)
            $table->date('target_month'); // الشهر المستهدف
            $table->string('month_name'); // اسم الشهر (للعرض)
            $table->year('fiscal_year'); // السنة المالية
            $table->decimal('target_amount', 12, 2); // المبلغ المستهدف
            $table->decimal('achieved_amount', 12, 2)->default(0); // المبلغ المحقق
            $table->decimal('achievement_percentage', 5, 2)->default(0); // نسبة التحقيق
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
            
            // تأكد من عدم تكرار نفس الهدف لنفس الشهر ونوع الإيراد
            $table->unique(['revenue_type_id', 'target_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_targets');
    }
};