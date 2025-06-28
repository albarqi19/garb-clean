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
        Schema::create('strategic_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_plan_id')->constrained('strategic_plans')->onDelete('cascade')->comment('الخطة الاستراتيجية المرتبطة');
            $table->string('code')->comment('رمز المؤشر مثل (ع1-1)');
            $table->string('name')->comment('اسم المؤشر');
            $table->text('description')->comment('بيان وصفي للمؤشر');
            $table->string('reference_number')->nullable()->comment('الرقم المرجعي إن وجد');
            $table->decimal('target_value', 15, 2)->comment('القيمة المستهدفة');
            $table->enum('result_type', ['number', 'percentage'])->default('number')->comment('نوع النتيجة: رقم أو نسبة');
            $table->enum('monitoring_type', ['cumulative', 'non_cumulative'])->default('non_cumulative')->comment('نوع الرصد: تراكمي أو غير تراكمي');
            $table->string('unit')->nullable()->comment('وحدة القياس');
            $table->string('responsible_department')->nullable()->comment('الإدارة المسؤولة');
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('المستخدم الذي أنشأ المؤشر');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['strategic_plan_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategic_indicators');
    }
};
