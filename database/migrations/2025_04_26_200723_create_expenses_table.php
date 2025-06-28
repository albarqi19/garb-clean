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
        // جدول أنواع المصروفات
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم نوع المصروف (رواتب، مبادرات، تسويق، مكافآت الحفظة، إلخ)
            $table->text('description')->nullable(); // وصف نوع المصروف
            $table->boolean('is_active')->default(true); // هل نوع المصروف مفعل
            $table->timestamps();
        });

        // جدول المصروفات
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->nullable()->constrained()->onDelete('set null'); // الحلقة (إذا كان المصروف للحلقة)
            $table->decimal('amount', 14, 2); // مبلغ المصروف
            $table->foreignId('expense_type_id')->constrained(); // نوع المصروف
            $table->string('month'); // الشهر
            $table->date('expense_date'); // تاريخ المصروف
            $table->foreignId('academic_term_id')->nullable()->constrained(); // الفصل الدراسي
            $table->string('transaction_reference')->nullable(); // مرجع المعاملة
            $table->string('beneficiary_name')->nullable(); // اسم المستفيد
            $table->boolean('is_for_center')->default(false); // هل المصروف للمركز (وليس لحلقة معينة)
            $table->text('notes')->nullable(); // ملاحظات
            $table->foreignId('approved_by')->nullable()->constrained('users'); // الشخص الذي وافق على المصروف
            $table->foreignId('recorded_by')->nullable()->constrained('users'); // الشخص الذي سجل المصروف
            $table->timestamps();
        });

        // جدول ميزانيات الحلقات
        Schema::create('circle_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->constrained()->onDelete('cascade'); // الحلقة
            $table->foreignId('academic_term_id')->constrained(); // الفصل الدراسي
            $table->decimal('total_budget', 14, 2); // إجمالي ميزانية الحلقة
            $table->decimal('salaries_budget', 14, 2); // ميزانية الرواتب للحلقة
            $table->decimal('initiatives_budget', 14, 2)->nullable(); // ميزانية المبادرات للحلقة
            $table->decimal('remaining_budget', 14, 2); // المبلغ المتبقي من الميزانية
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_budgets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_types');
    }
};
