<?php

namespace App\Services;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiToken;
    protected $isEnabled;

    public function __construct()
    {
        $this->apiUrl = WhatsAppSetting::get('api_url');
        $this->apiToken = WhatsAppSetting::get('api_token');
        $this->isEnabled = WhatsAppSetting::notificationsEnabled();
    }

    /**
     * Send a notification message.
     *
     * @param string $userType
     * @param int|null $userId
     * @param string $phoneNumber
     * @param string $content
     * @param string $messageType
     * @param array|null $metadata
     * @return bool
     */
    public function sendNotification(
        string $userType,
        ?int $userId,
        string $phoneNumber,
        string $content,
        string $messageType = 'notification',
        ?array $metadata = null
    ): bool {
        // التحقق من تفعيل النظام
        if (!$this->isEnabled) {
            Log::info('WhatsApp notifications are disabled');
            return false;
        }

        // التحقق من إعدادات API
        if (empty($this->apiUrl) || empty($this->apiToken)) {
            Log::warning('WhatsApp API not configured properly');
            return false;
        }

        // إنشاء رسالة في قاعدة البيانات
        $message = WhatsAppMessage::createNotification(
            $userType,
            $userId,
            $phoneNumber,
            $content,
            $messageType,
            $metadata
        );

        // محاولة الإرسال
        return $this->sendMessage($message);
    }    /**
     * Send a message to WhatsApp API.
     *
     * @param WhatsAppMessage $message
     * @return bool
     */
    protected function sendMessage(WhatsAppMessage $message): bool
    {
        try {
            // Dispatch job to queue for async processing
            \App\Jobs\SendWhatsAppMessage::dispatch($message->id);
            
            Log::info('WhatsApp message queued for sending', [
                'message_id' => $message->id,
                'phone' => $message->phone_number
            ]);

            return true;

        } catch (\Exception $e) {
            $message->markAsFailed($e->getMessage());
            
            Log::error('Exception while queueing WhatsApp message', [
                'message_id' => $message->id,
                'phone' => $message->phone_number,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Format phone number for WhatsApp.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // إزالة المسافات والرموز غير المرغوبة
        $phone = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // إضافة رمز الدولة للسعودية إذا لم يكن موجوداً
        if (!str_starts_with($phone, '+') && !str_starts_with($phone, '966')) {
            if (str_starts_with($phone, '05')) {
                $phone = '+966' . substr($phone, 1);
            } else {
                $phone = '+966' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Send a teacher notification.
     *
     * @param int $teacherId
     * @param string $content
     * @param string $messageType
     * @return bool
     */
    public function sendTeacherNotification(int $teacherId, string $content, string $messageType = 'notification'): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('teacher_notifications')) {
            return false;
        }

        $teacher = \App\Models\Teacher::find($teacherId);
        if (!$teacher || !$teacher->phone) {
            return false;
        }

        return $this->sendNotification(
            'teacher',
            $teacherId,
            $teacher->phone,
            $content,
            $messageType,
            ['teacher_id' => $teacherId, 'teacher_name' => $teacher->name]
        );
    }

    /**
     * Send a student notification.
     *
     * @param int $studentId
     * @param string $content
     * @param string $messageType
     * @return bool
     */
    public function sendStudentNotification(int $studentId, string $content, string $messageType = 'notification'): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('student_notifications')) {
            return false;
        }

        $student = \App\Models\Student::find($studentId);
        if (!$student || !$student->phone) {
            return false;
        }

        return $this->sendNotification(
            'student',
            $studentId,
            $student->phone,
            $content,
            $messageType,
            ['student_id' => $studentId, 'student_name' => $student->name]
        );
    }

    /**
     * Send a parent notification.
     *
     * @param int $studentId
     * @param string $content
     * @param string $messageType
     * @return bool
     */    /**
     * Test the WhatsApp connection.
     *
     * @param string $testPhoneNumber
     * @return bool
     */
    public function testConnection(string $testPhoneNumber): bool
    {
        return $this->sendNotification(
            'admin',
            null,
            $testPhoneNumber,
            'هذه رسالة اختبار من نظام مركز القرآن الكريم',
            'test',
            ['test' => true, 'timestamp' => now()->toISOString()]
        );
    }

    /**
     * Send teacher welcome notification.
     *
     * @param \App\Models\Teacher $teacher
     * @return bool
     */
    public function sendTeacherWelcomeNotification(\App\Models\Teacher $teacher): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('teacher_notifications')) {
            return false;
        }

        $templateService = app(WhatsAppTemplateService::class);
        $content = $templateService->getTeacherWelcomeMessage($teacher->name, $teacher->quranCircle->name ?? 'غير محدد');

        return $this->sendNotification(
            'teacher',
            $teacher->id,
            $teacher->phone,
            $content,
            'welcome',
            ['teacher_id' => $teacher->id, 'template' => 'teacher_welcome']
        );
    }

    /**
     * Send student welcome notification.
     *
     * @param \App\Models\Student $student
     * @return bool
     */    public function sendStudentWelcomeNotification(\App\Models\Student $student): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('student_notifications')) {
            return false;
        }

        $templateService = app(WhatsAppTemplateService::class);
        $content = $templateService->studentWelcomeMessage($student->name, $student->quranCircle->name ?? 'غير محدد');

        return $this->sendNotification(
            'student',
            $student->id,
            $student->phone,
            $content,
            'welcome',
            ['student_id' => $student->id, 'template' => 'student_welcome']
        );
    }

    /**
     * Send parent notification with template.
     *
     * @param string $phoneNumber
     * @param string $templateType
     * @param array $data
     * @return bool
     */
    public function sendParentNotification(string $phoneNumber, string $templateType, array $data): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('parent_notifications')) {
            return false;
        }

        $templateService = app(WhatsAppTemplateService::class);
        
        $content = match($templateType) {
            'student_welcome' => $templateService->getParentStudentWelcomeMessage(
                $data['guardian_name'] ?? 'ولي الأمر',
                $data['student_name'] ?? '',
                $data['circle_name'] ?? 'غير محدد',
                $data['center_name'] ?? 'مركز تحفيظ القرآن الكريم'
            ),
            'attendance_absent' => $templateService->getAttendanceAbsentMessage(
                $data['guardian_name'] ?? 'ولي الأمر',
                $data['student_name'] ?? '',
                $data['date'] ?? now()->format('Y-m-d')
            ),
            'session_completion' => $templateService->getSessionCompletionMessage(
                $data['guardian_name'] ?? 'ولي الأمر',
                $data['student_name'] ?? '',
                $data['session_details'] ?? ''
            ),
            default => 'إشعار من مركز تحفيظ القرآن الكريم'
        };

        return $this->sendNotification(
            'parent',
            $data['student_id'] ?? null,
            $phoneNumber,
            $content,
            $templateType,
            array_merge($data, ['template' => $templateType])
        );
    }

    /**
     * Send attendance notification.
     *
     * @param \App\Models\Student $student
     * @param string $status
     * @param string $date
     * @return bool
     */
    public function sendAttendanceNotification(\App\Models\Student $student, string $status, string $date): bool
    {
        if (!WhatsAppSetting::isNotificationEnabled('attendance_notifications')) {
            return false;
        }

        $templateService = app(WhatsAppTemplateService::class);
        
        // Send to student if they have a phone
        if ($student->phone) {
            $studentContent = $templateService->getStudentAttendanceMessage($student->name, $status, $date);
            $this->sendNotification(
                'student',
                $student->id,
                $student->phone,
                $studentContent,
                'attendance',
                ['student_id' => $student->id, 'status' => $status, 'date' => $date]
            );
        }

        // Send to parent if they have a phone and it's an absence
        if ($student->guardian_phone && $status === 'غائب') {
            return $this->sendParentNotification(
                $student->guardian_phone,
                'attendance_absent',
                [
                    'student_id' => $student->id,
                    'guardian_name' => $student->guardian_name,
                    'student_name' => $student->name,
                    'date' => $date
                ]
            );
        }

        return true;
    }
}
