<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique()->comment('مفتاح القالب (teacherWelcome, teacherLogin, etc.)');
            $table->string('template_name')->comment('اسم القالب العربي');
            $table->text('template_content')->comment('محتوى القالب');
            $table->text('description')->nullable()->comment('وصف القالب ومتغيراته');
            $table->json('variables')->nullable()->comment('المتغيرات المستخدمة في القالب');
            $table->enum('category', ['teacher', 'student', 'attendance', 'general'])->default('general')->comment('فئة القالب');
            $table->boolean('is_active')->default(true)->comment('هل القالب نشط');
            $table->timestamps();
        });

        // إدراج القوالب الافتراضية
        $this->insertDefaultTemplates();
    }

    /**
     * إدراج القوالب الافتراضية
     */
    private function insertDefaultTemplates(): void
    {
        $templates = [
            [
                'template_key' => 'teacher_welcome',
                'template_name' => 'رسالة ترحيب المعلم الجديد',
                'template_content' => "مرحباً الأستاذ {teacher_name} 📚\n\nتم إضافتك بنجاح في نظام مركز القرآن الكريم\nالمسجد: {mosque_name}\n\nبارك الله فيك وجعل عملك في خدمة كتاب الله في ميزان حسناتك 🤲",
                'description' => 'رسالة ترحيب ترسل للمعلم الجديد عند إضافته للنظام',
                'variables' => json_encode(['teacher_name', 'mosque_name']),
                'category' => 'teacher',
                'is_active' => true,
            ],
            [
                'template_key' => 'teacher_login',
                'template_name' => 'إشعار تسجيل دخول المعلم',
                'template_content' => "🔐 تسجيل دخول جديد\n\nالأستاذ: {teacher_name}\nالمسجد: {mosque_name}\nالوقت: {login_time}\n\nمرحباً بك في نظام مركز القرآن الكريم 📚",
                'description' => 'إشعار يرسل للمعلم عند تسجيل الدخول',
                'variables' => json_encode(['teacher_name', 'mosque_name', 'login_time']),
                'category' => 'teacher',
                'is_active' => true,
            ],
            [
                'template_key' => 'student_welcome',
                'template_name' => 'رسالة ترحيب الطالب الجديد',
                'template_content' => "مرحباً {student_name} 🌟\n\nتم تسجيلك بنجاح في حلقة {circle_name}\n\nنسأل الله أن يبارك في حفظك ويجعلك من حملة كتابه الكريم 📖✨",
                'description' => 'رسالة ترحيب للطالب الجديد',
                'variables' => json_encode(['student_name', 'circle_name']),
                'category' => 'student',
                'is_active' => true,
            ],
            [
                'template_key' => 'attendance_confirmation',
                'template_name' => 'تأكيد الحضور',
                'template_content' => "تم تسجيل حضور {student_name} ✅\n\n📅 التاريخ: {date}\n🕌 الحلقة: {circle_name}\n\nبارك الله فيك على المواظبة والحرص 🌟",
                'description' => 'رسالة تأكيد حضور الطالب',
                'variables' => json_encode(['student_name', 'date', 'circle_name']),
                'category' => 'attendance',
                'is_active' => true,
            ],
            [
                'template_key' => 'absence_notification',
                'template_name' => 'إشعار الغياب',
                'template_content' => "تنبيه غياب ⚠️\n\nالطالب: {student_name}\n📅 التاريخ: {date}\n🕌 الحلقة: {circle_name}\n\nنتطلع لحضورك في الجلسة القادمة بإذن الله 🤲",
                'description' => 'إشعار غياب الطالب',
                'variables' => json_encode(['student_name', 'date', 'circle_name']),
                'category' => 'attendance',
                'is_active' => true,
            ],
            [
                'template_key' => 'session_completion',
                'template_name' => 'إكمال جلسة التسميع',
                'template_content' => "تم إكمال جلسة التسميع ✅\n\nالطالب: {student_name}\nنوع الجلسة: {session_type}\nالمحتوى: {content}\nالتقدير: {grade}\n\nأحسنت، بارك الله فيك وزادك علماً وحفظاً 🌟📚",
                'description' => 'رسالة إكمال جلسة التسميع',
                'variables' => json_encode(['student_name', 'session_type', 'content', 'grade']),
                'category' => 'student',
                'is_active' => true,
            ],
            [
                'template_key' => 'session_reminder',
                'template_name' => 'تذكير جلسة التسميع',
                'template_content' => "تذكير جلسة التسميع ⏰\n\nالطالب: {student_name}\nالوقت: {time}\nالحلقة: {circle_name}\n\nنتطلع لحضورك وتسميعك بإذن الله 📚🤲",
                'description' => 'تذكير بموعد جلسة التسميع',
                'variables' => json_encode(['student_name', 'time', 'circle_name']),
                'category' => 'student',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('whatsapp_templates')->insert(array_merge($template, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
