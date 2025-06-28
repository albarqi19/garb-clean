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
        // جدول أنواع بنود العهدة (المسموح بها)
        Schema::create('custody_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم البند (حوافز الطلاب، ضيافة، قرطاسية، إلخ)
            $table->text('description')->nullable(); // وصف البند
            $table->boolean('is_active')->default(true); // هل البند مفعل
            $table->timestamps();
        });

        // جدول العهد المالية الرئيسي
        Schema::create('financial_custodies', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique(); // رقم الطلب
            $table->foreignId('requester_id')->constrained('users'); // مقدم الطلب
            $table->string('requester_job_title')->nullable(); // المسمى الوظيفي لمقدم الطلب
            $table->foreignId('mosque_id')->nullable()->constrained(); // المسجد المرتبط بالعهدة (إن وجد)
            $table->decimal('total_amount', 10, 2); // إجمالي مبلغ العهدة
            $table->enum('status', [
                'مقدم',
                'تحت المراجعة',
                'معتمد',
                'مرفوض',
                'تم الصرف',
                'تم التسوية'
            ])->default('مقدم'); // حالة الطلب
            $table->date('request_date'); // تاريخ تقديم الطلب
            $table->date('approval_date')->nullable(); // تاريخ الموافقة
            $table->date('disbursement_date')->nullable(); // تاريخ الصرف
            $table->foreignId('approved_by')->nullable()->constrained('users'); // الشخص الذي اعتمد الطلب
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });

        // جدول بنود العهد المالية (التفاصيل)
        Schema::create('financial_custody_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_custody_id')->constrained()->onDelete('cascade');
            $table->foreignId('custody_category_id')->constrained(); // نوع البند
            $table->string('description')->nullable(); // وصف البند
            $table->decimal('amount', 10, 2); // المبلغ المطلوب
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });

        // جدول فواتير وإيصالات العهد
        Schema::create('custody_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_custody_id')->constrained()->onDelete('cascade');
            $table->foreignId('custody_item_id')->nullable()->constrained('financial_custody_items'); // البند المرتبط بالفاتورة
            $table->string('receipt_number')->nullable(); // رقم الإيصال أو الفاتورة
            $table->string('supplier_name'); // اسم المورد أو الجهة التي صدرت منها الفاتورة
            $table->string('tax_number')->nullable(); // الرقم الضريبي للمورد
            $table->boolean('is_tax_invoice')->default(false); // هل هي فاتورة ضريبية
            $table->decimal('amount', 10, 2); // مبلغ الفاتورة
            $table->date('receipt_date'); // تاريخ الفاتورة
            $table->string('receipt_file_path')->nullable(); // مسار ملف الفاتورة المرفق
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custody_receipts');
        Schema::dropIfExists('financial_custody_items');
        Schema::dropIfExists('financial_custodies');
        Schema::dropIfExists('custody_categories');
    }
};
