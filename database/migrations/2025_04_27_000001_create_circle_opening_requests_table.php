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
        Schema::create('circle_opening_requests', function (Blueprint $table) {
            $table->id();
            
            // معلومات مقدم الطلب
            $table->string('requester_name'); // اسم مقدم الطلب
            $table->string('requester_phone'); // رقم الجوال للتواصل
            $table->string('requester_relation_to_circle'); // صلته بالحلقة
            
            // معلومات المسجد والحي
            $table->string('neighborhood'); // اسم الحي
            $table->foreignId('mosque_id')->nullable()->constrained()->nullOnDelete(); // رقم المسجد (إذا كان موجود بقاعدة البيانات)
            $table->string('mosque_name'); // اسم المسجد (في حالة لم يكن موجود بقاعدة البيانات)
            $table->string('mosque_location_url')->nullable(); // رابط موقع المسجد في خرائط قوقل
            $table->string('nearest_circle')->nullable(); // أقرب حلقة من المسجد
            
            // معلومات الحلقة
            $table->unsignedSmallInteger('number_of_circles_requested'); // عدد الحلقات المطلوب فتحها
            $table->boolean('had_previous_circles'); // هل كان في المسجد حلقات سابقة
            $table->unsignedSmallInteger('expected_students_number')->nullable(); // العدد المتوقع للطلاب
            $table->boolean('is_mosque_owner_welcoming'); // هل باني المسجد مرحب بفتح الحلقة
            $table->enum('circle_time', ['عصر', 'مغرب', 'عصر ومغرب', 'كل الأوقات']); // وقت الحلقة المطلوب
            
            // معلومات إضافية
            $table->text('notes')->nullable(); // ملاحظات أخرى
            $table->boolean('terms_accepted'); // اطلعت على الآلية والضوابط ومستعد للالتزام بها
            
            // معلومات متابعة الطلب
            $table->string('store_link')->nullable(); // رابط المتجر (يتم إضافته من قبل قسم التسويق)
            $table->enum('support_status', ['جاهز للانطلاق', 'متطوع', 'تم تأجيل الافتتاح', 'تم', 'قيد المعالجة'])->default('قيد المعالجة'); // حالة اكتمال الدعم
            $table->enum('teacher_availability', ['متوفر', 'غير متوفر', 'قيد البحث'])->default('قيد البحث'); // توفير معلمين
            $table->date('launch_date')->nullable(); // تاريخ انطلاق الحلقة
            $table->boolean('is_launched')->default(false); // هل انطلقت الحلقة؟
            
            // حالة الطلب العامة
            $table->enum('request_status', ['جديد', 'قيد المراجعة', 'موافق مبدئياً', 'موافق نهائياً', 'مرفوض', 'مكتمل'])->default('جديد');
            $table->string('rejection_reason')->nullable(); // سبب الرفض إذا تم رفض الطلب
            
            // تواريخ
            $table->integer('days_since_submission')->nullable(); // كم مضى يوم على التقديم
            $table->timestamps();
            $table->softDeletes(); // لحفظ تاريخ الطلبات حتى المحذوفة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_opening_requests');
    }
};