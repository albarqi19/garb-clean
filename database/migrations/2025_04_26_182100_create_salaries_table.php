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
        // جدول قواعد الرواتب والبدلات
        Schema::create('salary_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان القاعدة (مثل راتب معلم حفظ، بدل مشرف، إلخ)
            $table->decimal('amount', 10, 2); // المبلغ الأساسي
            $table->string('job_type'); // نوع الوظيفة (معلم، مشرف، إداري)
            $table->text('description')->nullable(); // وصف للقاعدة
            $table->boolean('is_active')->default(true); // هل القاعدة مفعلة
            $table->timestamps();
        });
        
        // جدول الحضور اليومي
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->morphs('attendable'); // يمكن أن يكون معلم أو موظف
            $table->date('date'); // تاريخ الحضور
            $table->enum('period', ['الفجر', 'العصر', 'المغرب', 'العشاء']); // الفترة
            $table->enum('status', ['حاضر', 'غائب', 'متأخر', 'مأذون']); // حالة الحضور
            $table->time('check_in')->nullable(); // وقت الحضور
            $table->time('check_out')->nullable(); // وقت الانصراف
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
        
        // جدول الرواتب الشهرية
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->morphs('payee'); // الشخص المستلم للراتب (معلم أو موظف)
            $table->foreignId('academic_term_id')->constrained(); // الفصل الدراسي
            $table->string('month'); // الشهر (مثل نوفمبر، ديسمبر)
            $table->decimal('base_amount', 10, 2); // المبلغ الأساسي
            $table->integer('attendance_days'); // عدد أيام الحضور
            $table->decimal('deductions', 10, 2)->default(0); // الاستقطاعات
            $table->decimal('bonuses', 10, 2)->default(0); // المكافآت والبدلات
            $table->decimal('total_amount', 10, 2); // المبلغ الإجمالي
            $table->date('payment_date')->nullable(); // تاريخ الدفع
            $table->boolean('is_paid')->default(false); // هل تم الدفع
            $table->text('payment_notes')->nullable(); // ملاحظات على الدفع
            $table->string('transaction_reference')->nullable(); // رقم مرجعي للمعاملة
            $table->string('iban'); // رقم الآيبان للتحويل
            $table->timestamps();
        });
        
        // جدول تفاصيل الرواتب (لتخزين تفاصيل كيفية حساب الراتب)
        Schema::create('salary_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_id')->constrained()->onDelete('cascade');
            $table->string('description'); // وصف البند (راتب أساسي، بدل نقل، استقطاع غياب)
            $table->decimal('amount', 10, 2); // المبلغ
            $table->enum('type', ['إضافة', 'خصم']); // نوع البند (إضافة أو خصم)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_details');
        Schema::dropIfExists('salaries');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('salary_rules');
    }
};
