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
        // جدول أنواع الإيرادات
        Schema::create('revenue_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم نوع الإيراد (المتجر الإلكتروني، حلق متوفين، مؤسسات مانحة، إلخ)
            $table->text('description')->nullable(); // وصف نوع الإيراد
            $table->boolean('is_active')->default(true); // هل نوع الإيراد مفعل
            $table->timestamps();
        });

        // جدول الإيرادات
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->nullable()->constrained()->onDelete('set null'); // الحلقة (إذا كان الإيراد للحلقة)
            $table->decimal('amount', 14, 2); // مبلغ الإيراد
            $table->foreignId('revenue_type_id')->constrained(); // نوع الإيراد
            $table->string('month'); // الشهر
            $table->date('revenue_date'); // تاريخ الإيراد
            $table->foreignId('academic_term_id')->nullable()->constrained(); // الفصل الدراسي
            $table->string('transaction_reference')->nullable(); // مرجع المعاملة
            $table->string('donor_name')->nullable(); // اسم المتبرع أو الجهة المانحة
            $table->string('donor_contact')->nullable(); // معلومات الاتصال بالمتبرع
            $table->boolean('is_for_center')->default(false); // هل الإيراد للمركز (وليس لحلقة معينة)
            $table->text('notes')->nullable(); // ملاحظات
            $table->foreignId('recorded_by')->nullable()->constrained('users'); // الشخص الذي سجل الإيراد
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
        Schema::dropIfExists('revenue_types');
    }
};
