<?php

namespace App\Services;

use App\Models\WhatsAppTemplate;

class WhatsAppTemplateService
{
    /**
     * Get processed template by key with variables
     *
     * @param string $templateKey
     * @param array $variables
     * @return string|null
     */
    public static function getProcessedTemplate(string $templateKey, array $variables = []): ?string
    {
        $template = WhatsAppTemplate::findByKey($templateKey);
        
        if (!$template) {
            // Fallback to static methods for backward compatibility
            return self::getStaticTemplate($templateKey, $variables);
        }
        
        return $template->getProcessedContent($variables);
    }
      /**
     * Get welcome message for new teacher.
     *
     * @param string $teacherName
     * @param string $mosqueName
     * @return string
     */
    public static function teacherWelcomeMessage(string $teacherName, string $mosqueName): string
    {
        return self::getProcessedTemplate('teacher_welcome', [
            'teacher_name' => $teacherName,
            'mosque_name' => $mosqueName,
        ]) ?? self::getStaticTeacherWelcome($teacherName, $mosqueName);
    }

    /**
     * Get welcome message for new teacher with password.
     *
     * @param string $teacherName
     * @param string $mosqueName
     * @param string $password
     * @param string $identityNumber
     * @return string
     */
    public static function teacherWelcomeWithPasswordMessage(string $teacherName, string $mosqueName, string $password, string $identityNumber): string
    {
        return self::getProcessedTemplate('teacher_welcome_with_password', [
            'teacher_name' => $teacherName,
            'mosque_name' => $mosqueName,
            'password' => $password,
            'identity_number' => $identityNumber,
        ]) ?? self::getStaticTeacherWelcomeWithPassword($teacherName, $mosqueName, $password, $identityNumber);
    }

    /**
     * Get login notification message for teacher.
     *
     * @param string $teacherName
     * @param string $mosqueName
     * @param string $loginTime
     * @return string
     */
    public static function teacherLoginMessage(string $teacherName, string $mosqueName, string $loginTime): string
    {
        return self::getProcessedTemplate('teacher_login', [
            'teacher_name' => $teacherName,
            'mosque_name' => $mosqueName,
            'login_time' => $loginTime,
        ]) ?? self::getStaticTeacherLogin($teacherName, $mosqueName, $loginTime);
    }

    /**
     * Get login notification message for supervisor.
     *
     * @param string $supervisorName
     * @param string $loginTime
     * @return string
     */
    public static function supervisorLoginMessage(string $supervisorName, string $loginTime): string
    {
        return self::getProcessedTemplate('supervisor_login', [
            'supervisor_name' => $supervisorName,
            'login_time' => $loginTime,
        ]) ?? self::getStaticSupervisorLogin($supervisorName, $loginTime);
    }

    /**
     * Get welcome message for new student.
     *
     * @param string $studentName
     * @param string $circleName
     * @return string
     */
    public static function studentWelcomeMessage(string $studentName, string $circleName): string
    {
        return self::getProcessedTemplate('student_welcome', [
            'student_name' => $studentName,
            'circle_name' => $circleName,
        ]) ?? self::getStaticStudentWelcome($studentName, $circleName);
    }

    /**
     * Get attendance confirmation message.
     *
     * @param string $studentName
     * @param string $date
     * @param string $circleName
     * @return string
     */
    public static function attendanceConfirmationMessage(string $studentName, string $date, string $circleName): string
    {
        return self::getProcessedTemplate('attendance_confirmation', [
            'student_name' => $studentName,
            'date' => $date,
            'circle_name' => $circleName,
        ]) ?? self::getStaticAttendanceConfirmation($studentName, $date, $circleName);
    }

    /**
     * Get absence notification message.
     *
     * @param string $studentName
     * @param string $date
     * @param string $circleName
     * @return string
     */
    public static function absenceNotificationMessage(string $studentName, string $date, string $circleName): string
    {
        return self::getProcessedTemplate('absence_notification', [
            'student_name' => $studentName,
            'date' => $date,
            'circle_name' => $circleName,
        ]) ?? self::getStaticAbsenceNotification($studentName, $date, $circleName);
    }

    /**
     * Get session completion message.
     *
     * @param string $studentName
     * @param string $sessionType
     * @param string $content
     * @param string $grade
     * @return string
     */
    public static function sessionCompletionMessage(string $studentName, string $sessionType, string $content, string $grade): string
    {
        return self::getProcessedTemplate('session_completion', [
            'student_name' => $studentName,
            'session_type' => $sessionType,
            'content' => $content,
            'grade' => $grade,
        ]) ?? self::getStaticSessionCompletion($studentName, $sessionType, $content, $grade);
    }

    /**
     * Get session reminder message.
     *
     * @param string $studentName
     * @param string $time
     * @param string $circleName
     * @return string
     */
    public static function sessionReminderMessage(string $studentName, string $time, string $circleName): string
    {
        return self::getProcessedTemplate('session_reminder', [
            'student_name' => $studentName,
            'time' => $time,
            'circle_name' => $circleName,
        ]) ?? self::getStaticSessionReminder($studentName, $time, $circleName);
    }

    /**
     * Get parent notification message.
     *
     * @param string $studentName
     * @param string $message
     * @param string $guardianName
     * @return string
     */
    public static function parentNotificationMessage(string $studentName, string $message, string $guardianName = ''): string
    {
        $greeting = $guardianName ? "حفظكم الله أ/ {$guardianName}" : "حفظكم الله";
        
        return self::getProcessedTemplate('parent_notification', [
            'greeting' => $greeting,
            'student_name' => $studentName,
            'message' => $message,
        ]) ?? self::getStaticParentNotification($studentName, $message, $guardianName);
    }

    /**
     * Get teacher assignment notification.
     *
     * @param string $teacherName
     * @param string $circleName
     * @param string $mosqueName
     * @return string
     */
    public static function teacherAssignmentMessage(string $teacherName, string $circleName, string $mosqueName): string
    {
        return self::getProcessedTemplate('teacher_assignment', [
            'teacher_name' => $teacherName,
            'circle_name' => $circleName,
            'mosque_name' => $mosqueName,
        ]) ?? self::getStaticTeacherAssignment($teacherName, $circleName, $mosqueName);
    }

    /**
     * Get exam notification message.
     *
     * @param string $studentName
     * @param string $examType
     * @param string $examDate
     * @param string $examTime
     * @return string
     */
    public static function examNotificationMessage(string $studentName, string $examType, string $examDate, string $examTime): string
    {
        return self::getProcessedTemplate('exam_notification', [
            'student_name' => $studentName,
            'exam_type' => $examType,
            'exam_date' => $examDate,
            'exam_time' => $examTime,
        ]) ?? self::getStaticExamNotification($studentName, $examType, $examDate, $examTime);
    }

    /**
     * Get progress report message.
     *
     * @param string $studentName
     * @param array $progressData
     * @return string
     */
    public static function progressReportMessage(string $studentName, array $progressData): string
    {
        $variables = array_merge(['student_name' => $studentName], $progressData);
        
        return self::getProcessedTemplate('progress_report', $variables) 
            ?? self::getStaticProgressReport($studentName, $progressData);
    }

    /**
     * Get general announcement message.
     *
     * @param string $title
     * @param string $content
     * @param string $sender
     * @return string
     */
    public static function announcementMessage(string $title, string $content, string $sender = 'إدارة المركز'): string
    {
        return self::getProcessedTemplate('general_announcement', [
            'title' => $title,
            'content' => $content,
            'sender' => $sender,
        ]) ?? self::getStaticAnnouncement($title, $content, $sender);
    }

    /**
     * Get birthday greeting message.
     *
     * @param string $name
     * @param string $userType
     * @return string
     */
    public static function birthdayGreetingMessage(string $name, string $userType = 'طالب'): string
    {
        return self::getProcessedTemplate('birthday_greeting', [
            'name' => $name,
        ]) ?? self::getStaticBirthdayGreeting($name, $userType);
    }
    
    // =====================================================
    // Static fallback methods for backward compatibility
    // =====================================================
      /**
     * Fallback method to get static templates
     */
    private static function getStaticTemplate(string $templateKey, array $variables): ?string
    {        return match($templateKey) {            'teacher_welcome' => self::getStaticTeacherWelcome($variables['teacher_name'] ?? '', $variables['mosque_name'] ?? ''),
            'teacher_welcome_with_password' => self::getStaticTeacherWelcomeWithPassword($variables['teacher_name'] ?? '', $variables['mosque_name'] ?? '', $variables['password'] ?? $variables['plain_password'] ?? '', $variables['identity_number'] ?? ''),
            'teacher_login' => self::getStaticTeacherLogin($variables['teacher_name'] ?? '', $variables['mosque_name'] ?? '', $variables['login_time'] ?? ''),
            'supervisor_login' => self::getStaticSupervisorLogin($variables['supervisor_name'] ?? '', $variables['login_time'] ?? ''),
            'student_welcome' => self::getStaticStudentWelcome($variables['student_name'] ?? '', $variables['circle_name'] ?? ''),
            'attendance_confirmation' => self::getStaticAttendanceConfirmation($variables['student_name'] ?? '', $variables['date'] ?? '', $variables['circle_name'] ?? ''),
            'absence_notification' => self::getStaticAbsenceNotification($variables['student_name'] ?? '', $variables['date'] ?? '', $variables['circle_name'] ?? ''),
            'session_completion' => self::getStaticSessionCompletion($variables['student_name'] ?? '', $variables['session_type'] ?? '', $variables['content'] ?? '', $variables['grade'] ?? ''),
            'session_reminder' => self::getStaticSessionReminder($variables['student_name'] ?? '', $variables['time'] ?? '', $variables['circle_name'] ?? ''),
            'parent_notification' => self::getStaticParentNotification($variables['student_name'] ?? '', $variables['message'] ?? '', $variables['guardian_name'] ?? ''),
            'teacher_assignment' => self::getStaticTeacherAssignment($variables['teacher_name'] ?? '', $variables['circle_name'] ?? '', $variables['mosque_name'] ?? ''),
            'exam_notification' => self::getStaticExamNotification($variables['student_name'] ?? '', $variables['exam_type'] ?? '', $variables['exam_date'] ?? '', $variables['exam_time'] ?? ''),
            'progress_report' => self::getStaticProgressReport($variables['student_name'] ?? '', $variables),
            'general_announcement' => self::getStaticAnnouncement($variables['title'] ?? '', $variables['content'] ?? '', $variables['sender'] ?? 'إدارة المركز'),
            'birthday_greeting' => self::getStaticBirthdayGreeting($variables['name'] ?? '', 'طالب'),
            default => null,
        };
    }    private static function getStaticTeacherWelcome(string $teacherName, string $mosqueName): string
    {
        return "مرحباً الأستاذ {$teacherName} 📚\n\n" .
               "تم إضافتك بنجاح في نظام مركز القرآن الكريم\n" .
               "المسجد: {$mosqueName}\n\n" .
               "بارك الله فيك وجعل عملك في خدمة كتاب الله في ميزان حسناتك 🤲";
    }    private static function getStaticTeacherWelcomeWithPassword(string $teacherName, string $mosqueName, string $password, string $identityNumber): string
    {
        return "أهلا بالأستاذ {$teacherName} 📚\n\n" .
               "تم إضافتك بنجاح في منصة غرب لإدارة حلقات القرآن الكريم\n" .
               "المسجد: {$mosqueName}\n\n" .
               "📱 بيانات تسجيل الدخول:\n" .
               "الرابط: appgarb.vercel.app\n" .
               "اسم المستخدم: {$identityNumber}\n" .
               "كلمة المرور: {$password}\n\n" .
               "⚠️ احفظ كلمة المرور في مكان آمن\n" .
               "يمكنك تغييرها بعد تسجيل الدخول\n\n" .
               "بارك الله فيك وجعل عملك في خدمة كتاب الله في ميزان حسناتك 🤲";
    }
    
    private static function getStaticTeacherLogin(string $teacherName, string $mosqueName, string $loginTime): string
    {
        return "🔐 تسجيل دخول جديد\n\n" .
               "الأستاذ: {$teacherName}\n" .
               "المسجد: {$mosqueName}\n" .
               "الوقت: {$loginTime}\n\n" .
               "مرحباً بك في نظام مركز القرآن الكريم 📚";
    }

    private static function getStaticSupervisorLogin(string $supervisorName, string $loginTime): string
    {
        return "🔐 تسجيل دخول المشرف\n\n" .
               "المشرف: {$supervisorName}\n" .
               "الوقت: {$loginTime}\n\n" .
               "مرحباً بك في نظام إدارة المركز 📊";
    }

    private static function getStaticStudentWelcome(string $studentName, string $circleName): string
    {
        return "مرحباً {$studentName} 🌟\n\n" .
               "تم تسجيلك بنجاح في حلقة {$circleName}\n\n" .
               "نسأل الله أن يبارك في حفظك ويجعلك من حملة كتابه الكريم 📖✨";
    }

    private static function getStaticAttendanceConfirmation(string $studentName, string $date, string $circleName): string
    {
        return "تم تسجيل حضور {$studentName} ✅\n\n" .
               "📅 التاريخ: {$date}\n" .
               "🕌 الحلقة: {$circleName}\n\n" .
               "بارك الله فيك على المواظبة والحرص 🌟";
    }

    private static function getStaticAbsenceNotification(string $studentName, string $date, string $circleName): string
    {
        return "تنبيه غياب ⚠️\n\n" .
               "الطالب: {$studentName}\n" .
               "📅 التاريخ: {$date}\n" .
               "🕌 الحلقة: {$circleName}\n\n" .
               "نتطلع لحضورك في الجلسة القادمة بإذن الله 🤲";
    }

    private static function getStaticSessionCompletion(string $studentName, string $sessionType, string $content, string $grade): string
    {
        return "تم إكمال جلسة التسميع ✅\n\n" .
               "الطالب: {$studentName}\n" .
               "نوع الجلسة: {$sessionType}\n" .
               "المحتوى: {$content}\n" .
               "التقدير: {$grade}\n\n" .
               "أحسنت، بارك الله فيك وزادك علماً وحفظاً 🌟📚";
    }

    private static function getStaticSessionReminder(string $studentName, string $time, string $circleName): string
    {
        return "تذكير جلسة التسميع ⏰\n\n" .
               "الطالب: {$studentName}\n" .
               "الوقت: {$time}\n" .
               "الحلقة: {$circleName}\n\n" .
               "لا تنس حضور جلسة التسميع، بارك الله فيك 🤲";
    }

    private static function getStaticParentNotification(string $studentName, string $message, string $guardianName = ''): string
    {
        $greeting = $guardianName ? "حفظكم الله أ/ {$guardianName}" : "حفظكم الله";
        
        return "{$greeting} 🌹\n\n" .
               "تحديث حول الطالب: {$studentName}\n\n" .
               $message . "\n\n" .
               "جزاكم الله خيراً على متابعتكم وحرصكم 🤲\n" .
               "مركز القرآن الكريم";
    }

    private static function getStaticTeacherAssignment(string $teacherName, string $circleName, string $mosqueName): string
    {
        return "تكليف جديد 📋\n\n" .
               "الأستاذ الفاضل: {$teacherName}\n" .
               "تم تكليفك بحلقة: {$circleName}\n" .
               "المسجد: {$mosqueName}\n\n" .
               "نسأل الله أن يبارك في جهودكم ويجعلها في ميزان حسناتكم 🤲";
    }

    private static function getStaticExamNotification(string $studentName, string $examType, string $examDate, string $examTime): string
    {
        return "إشعار اختبار 📝\n\n" .
               "الطالب: {$studentName}\n" .
               "نوع الاختبار: {$examType}\n" .
               "📅 التاريخ: {$examDate}\n" .
               "🕐 الوقت: {$examTime}\n\n" .
               "ندعو لك بالتوفيق والنجاح 🤲✨";
    }

    private static function getStaticProgressReport(string $studentName, array $progressData): string
    {
        $message = "تقرير التقدم الأسبوعي 📊\n\n";
        $message .= "الطالب: {$studentName}\n\n";
        
        if (isset($progressData['attendance'])) {
            $message .= "📈 الحضور: {$progressData['attendance']}%\n";
        }
        
        if (isset($progressData['memorized_verses'])) {
            $message .= "📚 الآيات المحفوظة: {$progressData['memorized_verses']}\n";
        }
        
        if (isset($progressData['current_surah'])) {
            $message .= "📖 السورة الحالية: {$progressData['current_surah']}\n";
        }
        
        $message .= "\nواصل تقدمك الممتاز، بارك الله فيك 🌟";
        
        return $message;
    }

    private static function getStaticAnnouncement(string $title, string $content, string $sender = 'إدارة المركز'): string
    {
        return "📢 {$title}\n\n" .
               $content . "\n\n" .
               "ــــــــــــــــــــــــــــ\n" .
               "{$sender}\n" .
               "مركز القرآن الكريم";
    }

    private static function getStaticBirthdayGreeting(string $name, string $userType = 'طالب'): string
    {
        return "🎉 كل عام وأنت بخير 🎂\n\n" .
               "نبارك لـ {$name}\n" .
               "بمناسبة عيد ميلادك\n\n" .
               "أعاده الله عليك بالخير والبركة\n" .
               "وجعل عامك الجديد مليئاً بالإنجازات 🌟\n\n" .
               "مركز القرآن الكريم 🤲";
    }

    /**
     * Get student attendance message (for all attendance statuses).
     *
     * @param string $studentName
     * @param string $status
     * @param string $date
     * @return string
     */
    public static function getStudentAttendanceMessage(string $studentName, string $status, string $date): string
    {
        switch ($status) {
            case 'غائب':
                return self::absenceNotificationMessage($studentName, $date, 'الحلقة');
            case 'حاضر':
                return self::attendanceConfirmationMessage($studentName, $date, 'الحلقة');
            case 'متأخر':
                return "السلام عليكم {$studentName}, تم تسجيل تأخيرك بتاريخ {$date}. نرجو الحرص على الحضور في الوقت المناسب.";
            case 'مأذون':
                return "السلام عليكم {$studentName}, تم تسجيل إذنك بتاريخ {$date}. نتطلع لحضورك في المرة القادمة.";
            default:
                return "السلام عليكم {$studentName}, تم تسجيل حضورك بحالة: {$status} بتاريخ {$date}.";
        }
    }
}
