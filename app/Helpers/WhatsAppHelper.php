<?php

namespace App\Helpers;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppTemplateService;
use App\Jobs\SendWhatsAppMessage;

class WhatsAppHelper
{    /**
     * إرسال رسالة ترحيب للمعلم الجديد مع كلمة المرور
     *
     * @param object $teacher
     * @param string|null $password كلمة المرور الاختيارية
     * @return bool
     */
    public static function sendTeacherWelcomeWithPassword($teacher, ?string $password = null): bool
    {
        // التحقق من وجود رقم الهاتف
        if (empty($teacher->phone)) {
            return false;
        }

        // استخدام كلمة المرور المرسلة أو الموجودة في النموذج
        $teacherPassword = $password ?? $teacher->plain_password ?? null;
        
        if (!$teacherPassword) {
            // إذا لم تكن كلمة المرور متاحة، لا نستطيع إرسال الرسالة
            return false;
        }

        // الحصول على اسم المسجد
        $mosqueName = $teacher->mosque?->name ?? 'غير محدد';

        // الحصول على الرسالة من خدمة القوالب
        $messageContent = WhatsAppTemplateService::teacherWelcomeWithPasswordMessage(
            $teacher->name,
            $mosqueName,
            $teacherPassword,
            $teacher->identity_number
        );

        // إنشاء رسالة واتساب
        $whatsappMessage = WhatsAppMessage::createNotification(
            'teacher',
            $teacher->id,
            $teacher->phone,
            $messageContent,
            'notification',
            [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'mosque_name' => $mosqueName,
                'template_type' => 'teacher_welcome_with_password'
            ]
        );

        // إضافة الرسالة إلى queue للإرسال
        SendWhatsAppMessage::dispatch($whatsappMessage->id);

        return true;
    }

    /**
     * إرسال رسالة ترحيب عادية للمعلم
     *
     * @param object $teacher
     * @return bool
     */
    public static function sendTeacherWelcome($teacher): bool
    {
        // التحقق من وجود رقم الهاتف
        if (empty($teacher->phone)) {
            return false;
        }

        // الحصول على اسم المسجد
        $mosqueName = $teacher->mosque?->name ?? 'غير محدد';

        // الحصول على الرسالة من خدمة القوالب
        $messageContent = WhatsAppTemplateService::teacherWelcomeMessage(
            $teacher->name,
            $mosqueName
        );        // إنشاء رسالة واتساب
        $whatsappMessage = WhatsAppMessage::createNotification(
            'teacher',
            $teacher->id,
            $teacher->phone,
            $messageContent,
            'notification',
            [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'mosque_name' => $mosqueName,
                'template_type' => 'teacher_welcome'
            ]
        );

        // إضافة الرسالة إلى queue للإرسال
        SendWhatsAppMessage::dispatch($whatsappMessage->id);

        return true;
    }    /**
     * إرسال رسالة ترحيب للطالب الجديد
     *
     * @param object $student
     * @return bool
     */
    public static function sendStudentWelcome($student): bool
    {
        // التحقق من وجود رقم الهاتف
        if (empty($student->phone)) {
            return false;
        }

        // الحصول على اسم الحلقة
        $circleName = $student->quranCircle?->name ?? 'غير محدد';

        // الحصول على الرسالة من خدمة القوالب
        $messageContent = WhatsAppTemplateService::studentWelcomeMessage(
            $student->name,
            $circleName
        );        // إنشاء رسالة واتساب
        $whatsappMessage = WhatsAppMessage::createNotification(
            'student',
            $student->id,
            $student->phone,
            $messageContent,
            'notification',
            [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'circle_name' => $circleName,
                'template_type' => 'student_welcome'
            ]
        );

        // إضافة الرسالة إلى queue للإرسال
        SendWhatsAppMessage::dispatch($whatsappMessage->id);

        return true;
    }

    /**
     * إرسال إشعار لولي الأمر عن تسجيل طالب جديد
     *
     * @param object $student
     * @return bool
     */
    public static function sendParentNotification($student): bool
    {
        // التحقق من وجود رقم هاتف ولي الأمر
        if (empty($student->guardian_phone)) {
            return false;
        }

        // الحصول على اسم الحلقة
        $circleName = $student->quranCircle?->name ?? 'غير محدد';

        // إنشاء رسالة إشعار لولي الأمر
        $message = "تم تسجيل ابنك/ابنتك {$student->name} بنجاح في حلقة {$circleName}. نسأل الله أن يبارك في حفظه ويجعله من حملة كتابه الكريم.";

        // الحصول على الرسالة من خدمة القوالب
        $messageContent = WhatsAppTemplateService::parentNotificationMessage(
            $student->name,
            $message,
            $student->guardian_name ?? ''
        );

        // إنشاء رسالة واتساب
        $whatsappMessage = WhatsAppMessage::createNotification(
            'parent',
            $student->id,
            $student->guardian_phone,
            $messageContent,
            'notification',
            [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'guardian_name' => $student->guardian_name,
                'circle_name' => $circleName,
                'template_type' => 'parent_notification'
            ]
        );

        // إضافة الرسالة إلى queue للإرسال
        SendWhatsAppMessage::dispatch($whatsappMessage->id);

        return true;
    }

    /**
     * إرسال رسالة مخصصة مع كلمة مرور للمعلم
     *
     * @param object $teacher المعلم
     * @param string $customPassword كلمة المرور المخصصة
     * @param string|null $customMessage رسالة مخصصة (اختيارية)
     * @return bool حالة الإرسال
     */
    public static function sendCustomPasswordMessage($teacher, string $customPassword, ?string $customMessage = null): bool
    {
        // التحقق من وجود رقم الهاتف
        if (empty($teacher->phone)) {
            return false;
        }

        // الحصول على اسم المسجد
        $mosqueName = $teacher->mosque?->name ?? 'غير محدد';

        // إنشاء رسالة مخصصة أو استخدام القالب الافتراضي
        if ($customMessage) {
            $messageContent = $customMessage;
        } else {
            $messageContent = WhatsAppTemplateService::teacherWelcomeWithPasswordMessage(
                $teacher->name,
                $mosqueName,
                $customPassword,
                $teacher->identity_number
            );
        }        // إنشاء رسالة واتساب
        $whatsappMessage = WhatsAppMessage::createNotification(
            'teacher',
            $teacher->id,
            $teacher->phone,
            $messageContent,
            'custom',
            [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'mosque_name' => $mosqueName,
                'custom_password' => $customPassword,
                'template_type' => 'custom_password_message'
            ]
        );

        // إضافة الرسالة إلى queue للإرسال
        SendWhatsAppMessage::dispatch($whatsappMessage->id);

        return true;
    }
}
