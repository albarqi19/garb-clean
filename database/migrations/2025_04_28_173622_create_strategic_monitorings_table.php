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
        Schema::create('strategic_monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_indicator_id')->constrained('strategic_indicators')->onDelete('cascade')->comment('المؤشر الاستراتيجي المرتبط');
            $table->enum('period', ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter'])->comment('فترة الرصد: الربع الأول، الثاني، الثالث، الرابع');
            $table->integer('year')->comment('السنة');
            $table->decimal('achieved_value', 15, 2)->comment('القيمة المتحققة');
            $table->decimal('achievement_percentage', 10, 2)->nullable()->comment('نسبة التحقق المحسوبة');
            $table->text('notes')->nullable()->comment('ملاحظات على الرصد');
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('المستخدم الذي سجل الرصد');
            $table->timestamps();
            
            $table->unique(['strategic_indicator_id', 'period', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategic_monitorings');
    }
};
