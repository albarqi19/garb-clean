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
        Schema::table('curriculum_plans', function (Blueprint $table) {
            // إضافة حقول منشئ المناهج
            $table->text('description')->nullable()->after('name'); // وصف الخطة
            $table->enum('type', ['ثلاثي', 'ثنائي', 'حفظ فقط', 'مراجعة فقط'])->default('ثلاثي')->after('description'); // نوع الخطة
            $table->enum('period', ['يومي', 'أسبوعي', 'شهري'])->default('يومي')->after('type'); // فترة الخطة
            $table->integer('total_days')->nullable()->after('period'); // إجمالي الأيام
            $table->json('plan_data')->nullable()->after('total_days'); // بيانات الخطة اليومية
            $table->foreignId('created_by')->nullable()->after('plan_data')->constrained('users')->onDelete('set null'); // منشئ الخطة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_plans', function (Blueprint $table) {
            // حذف الحقول المضافة
            $table->dropColumn([
                'description',
                'type', 
                'period',
                'total_days',
                'plan_data',
                'created_by'
            ]);
        });
    }
};
