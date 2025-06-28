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
        Schema::create('circle_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->constrained()->onDelete('cascade'); // الحلقة المستفيدة
            $table->string('sponsor_name')->nullable(); // اسم الداعم
            $table->decimal('amount', 10, 2); // المبلغ الإضافي للحلقة
            $table->decimal('remaining_amount', 10, 2); // المبلغ المتبقي للتوزيع
            $table->date('allocation_date'); // تاريخ تخصيص المبلغ
            $table->string('month'); // الشهر المخصص له المبلغ
            $table->foreignId('academic_term_id')->constrained(); // الفصل الدراسي
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });

        // جدول توزيع حوافز الحلقات على المعلمين
        Schema::create('teacher_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_incentive_id')->constrained()->onDelete('cascade'); // مرجع لحافز الحلقة
            $table->foreignId('teacher_id')->constrained(); // المعلم المستفيد
            $table->decimal('amount', 10, 2); // المبلغ المخصص للمعلم
            $table->string('reason')->nullable(); // سبب الحافز
            $table->foreignId('approved_by')->nullable()->constrained('users'); // الشخص الذي وافق على الحافز
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_incentives');
        Schema::dropIfExists('circle_incentives');
    }
};
