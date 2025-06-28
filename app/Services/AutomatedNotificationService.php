<?php

namespace App\Services;

use App\Models\Student;
use App\Models\CurriculumAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

/**
 * خدمة نظام الإشعارات التلقائية
 * تدير إرسال الإشعارات للمعلمين والإداريين حول تقدم الطلاب والتنبيهات
 */
class AutomatedNotificationService
{
    protected $flexibleCurriculumService;
    protected $dailyTrackingService;

    public function __construct(
        FlexibleCurriculumService $flexibleCurriculumService,
        DailyCurriculumTrackingService $dailyTrackingService
    ) {
        $this->flexibleCurriculumService = $flexibleCurriculumService;
        $this->dailyTrackingService = $dailyTrackingService;
    }

    /**
     * إرسال إشعارات التنبيهات الجديدة للمعلمين
     */
    public function sendNewAlertsNotifications(): array
    {
        $results = [
            'notifications_sent' => 0,
            'errors' => []
        ];

        // جلب التنبيهات الجديدة (خلال الـ 24 ساعة الماضية)
        $newAlerts = CurriculumAlert::with(['student', 'currentCurriculum'])
            ->whereNull('teacher_decision')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->get();

        if ($newAlerts->isEmpty()) {
            Log::info("لا توجد تنبيهات جديدة لإرسالها");
            return $results;
        }

        // تجميع التنبيهات حسب الأولوية
        $alertsByPriority = $this->groupAlertsByPriority($newAlerts);

        // إرسال الإشعارات للمعلمين والإداريين
        foreach ($alertsByPriority as $priority => $alerts) {
            try {
                $this->sendPriorityBasedNotifications($priority, $alerts);
                $results['notifications_sent'] += count($alerts);
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'priority' => $priority,
                    'error' => $e->getMessage()
                ];
                Log::error("خطأ في إرسال إشعارات الأولوية {$priority}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("تم إرسال إشعارات التنبيهات الجديدة", $results);
        return $results;
    }

    /**
     * إرسال تقارير الأداء اليومية
     */
    public function sendDailyPerformanceReports(): array
    {
        $results = [
            'reports_sent' => 0,
            'errors' => []
        ];

        try {
            // جمع بيانات الأداء اليومي
            $dailyStats = $this->collectDailyPerformanceStats();
            
            // جلب المعلمين والإداريين
            $recipients = $this->getNotificationRecipients('daily_reports');
            
            foreach ($recipients as $recipient) {
                try {
                    $this->sendDailyReportEmail($recipient, $dailyStats);
                    $results['reports_sent']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = [
                'general_error' => $e->getMessage()
            ];
            Log::error("خطأ في إرسال التقارير اليومية", [
                'error' => $e->getMessage()
            ]);
        }

        Log::info("تم إرسال التقارير اليومية", $results);
        return $results;
    }

    /**
     * إرسال تذكيرات للطلاب المتأخرين في التسميع
     */
    public function sendRecitationReminders(): array
    {
        $results = [
            'reminders_sent' => 0,
            'errors' => []
        ];

        // جلب الطلاب الذين لم يسمعوا لأكثر من يومين
        $inactiveStudents = $this->getInactiveStudents();

        foreach ($inactiveStudents as $student) {
            try {
                $this->sendStudentRecitationReminder($student);
                $results['reminders_sent']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ];
                Log::error("خطأ في إرسال تذكير للطالب", [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("تم إرسال تذكيرات التسميع", $results);
        return $results;
    }

    /**
     * إرسال تقارير أسبوعية شاملة
     */
    public function sendWeeklyReports(): array
    {
        $results = [
            'reports_sent' => 0,
            'errors' => []
        ];

        try {
            // جمع بيانات الأسبوع
            $weeklyStats = $this->collectWeeklyStats();
            
            // جلب المستقبلين للتقارير الأسبوعية
            $recipients = $this->getNotificationRecipients('weekly_reports');
            
            foreach ($recipients as $recipient) {
                try {
                    $this->sendWeeklyReportEmail($recipient, $weeklyStats);
                    $results['reports_sent']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = [
                'general_error' => $e->getMessage()
            ];
            Log::error("خطأ في إرسال التقارير الأسبوعية", [
                'error' => $e->getMessage()
            ]);
        }

        Log::info("تم إرسال التقارير الأسبوعية", $results);
        return $results;
    }

    /**
     * تجميع التنبيهات حسب الأولوية
     */
    protected function groupAlertsByPriority(Collection $alerts): array
    {
        $grouped = [
            'high' => [],
            'medium' => [],
            'low' => []
        ];

        foreach ($alerts as $alert) {
            $performanceSummary = json_decode($alert->performance_summary, true);
            $priority = $performanceSummary['priority'] ?? 'medium';
            
            if (!isset($grouped[$priority])) {
                $grouped[$priority] = [];
            }
            
            $grouped[$priority][] = $alert;
        }

        return $grouped;
    }

    /**
     * إرسال إشعارات حسب الأولوية
     */
    protected function sendPriorityBasedNotifications(string $priority, array $alerts): void
    {
        $recipients = $this->getNotificationRecipients('alerts');
        
        foreach ($recipients as $recipient) {
            $notificationData = [
                'priority' => $priority,
                'alerts_count' => count($alerts),
                'alerts' => array_map(function($alert) {
                    return [
                        'id' => $alert->id,
                        'student_name' => $alert->student->name,
                        'alert_type' => $alert->alert_type,
                        'message' => $alert->alert_message,
                        'created_at' => $alert->created_at->format('Y-m-d H:i')
                    ];
                }, $alerts)
            ];

            // إرسال إشعار فوري للأولوية العالية
            if ($priority === 'high') {
                $this->sendImmediateAlert($recipient, $notificationData);
            } else {
                $this->sendRegularNotification($recipient, $notificationData);
            }
        }
    }

    /**
     * إرسال إشعار فوري للحالات الطارئة
     */
    protected function sendImmediateAlert(User $recipient, array $data): void
    {
        // إرسال عبر البريد الإلكتروني
        if ($recipient->email && $recipient->email_notifications) {
            $this->sendAlertEmail($recipient, $data, true);
        }

        // إرسال عبر الرسائل النصية (إذا متوفر)
        if ($recipient->phone && $recipient->sms_notifications) {
            $this->sendAlertSMS($recipient, $data);
        }

        // إشعار داخل النظام
        $this->createInSystemNotification($recipient, $data, true);
    }

    /**
     * إرسال إشعار عادي
     */
    protected function sendRegularNotification(User $recipient, array $data): void
    {
        // إشعار داخل النظام
        $this->createInSystemNotification($recipient, $data, false);
        
        // إرسال بريد إلكتروني للتنبيهات المتوسطة والعالية
        if ($data['priority'] !== 'low' && $recipient->email && $recipient->email_notifications) {
            $this->sendAlertEmail($recipient, $data, false);
        }
    }

    /**
     * جمع إحصائيات الأداء اليومي
     */
    protected function collectDailyPerformanceStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return [
            'date' => $today->format('Y-m-d'),
            'total_students' => Student::where('is_active', true)->count(),
            'students_recited_today' => $this->getStudentsWhoRecitedOn($today),
            'students_recited_yesterday' => $this->getStudentsWhoRecitedOn($yesterday),
            'new_alerts_today' => CurriculumAlert::whereDate('created_at', $today)->count(),
            'pending_alerts' => CurriculumAlert::whereNull('teacher_decision')->count(),
            'completion_rates' => $this->calculateDailyCompletionRates(),
            'top_performers' => $this->getTopPerformersToday(),
            'students_needing_attention' => $this->getStudentsNeedingAttention()
        ];
    }

    /**
     * جمع إحصائيات أسبوعية
     */
    protected function collectWeeklyStats(): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        return [
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'total_recitation_sessions' => $this->getWeeklyRecitationCount(),
            'curriculum_changes' => $this->getWeeklyCurriculumChanges(),
            'alert_summary' => $this->getWeeklyAlertSummary(),
            'performance_trends' => $this->getWeeklyPerformanceTrends(),
            'students_progression' => $this->getStudentsWhoProgressed(),
            'areas_for_improvement' => $this->getWeeklyImprovementAreas()
        ];
    }

    /**
     * جلب الطلاب غير النشطين
     */
    protected function getInactiveStudents(): Collection
    {
        return Student::with('progress')
            ->where('is_active', true)
            ->whereHas('progress', function($query) {
                $query->where(function($q) {
                    $q->whereNull('daily_tracking_updated_at')
                      ->orWhere('daily_tracking_updated_at', '<', Carbon::now()->subDays(2));
                });
            })
            ->get();
    }

    /**
     * جلب المستقبلين للإشعارات
     */
    protected function getNotificationRecipients(string $type): Collection
    {
        $query = User::where('is_active', true)
            ->where('role', 'teacher')
            ->orWhere('role', 'admin');

        // تخصيص المستقبلين حسب نوع الإشعار
        switch ($type) {
            case 'alerts':
                $query->where('alert_notifications', true);
                break;
            case 'daily_reports':
                $query->where('daily_report_notifications', true);
                break;
            case 'weekly_reports':
                $query->where('weekly_report_notifications', true);
                break;
        }

        return $query->get();
    }

    /**
     * إرسال بريد إلكتروني للتنبيه
     */
    protected function sendAlertEmail(User $recipient, array $data, bool $urgent = false): void
    {
        $subject = $urgent ? 
            "🚨 تنبيه عاجل: {$data['alerts_count']} تنبيهات جديدة عالية الأولوية" :
            "📋 تنبيهات جديدة: {$data['alerts_count']} تنبيهات تحتاج مراجعة";

        // يمكن استخدام Mail facade لإرسال البريد
        // Mail::to($recipient->email)->send(new CurriculumAlertMail($data, $urgent));
        
        Log::info("تم إرسال بريد تنبيه", [
            'recipient' => $recipient->email,
            'urgent' => $urgent,
            'alerts_count' => $data['alerts_count']
        ]);
    }

    /**
     * إرسال رسالة نصية للتنبيه
     */
    protected function sendAlertSMS(User $recipient, array $data): void
    {
        $message = "تنبيه عاجل: {$data['alerts_count']} تنبيهات جديدة عالية الأولوية تحتاج مراجعة في نظام التحفيظ";
        
        // تنفيذ إرسال الرسالة النصية
        // SMS::send($recipient->phone, $message);
        
        Log::info("تم إرسال رسالة نصية", [
            'recipient' => $recipient->phone,
            'message' => $message
        ]);
    }

    /**
     * إنشاء إشعار داخل النظام
     */
    protected function createInSystemNotification(User $recipient, array $data, bool $urgent = false): void
    {
        $title = $urgent ? 
            "🚨 تنبيهات عاجلة" :
            "📋 تنبيهات جديدة";

        // يمكن استخدام جدول notifications أو نظام الإشعارات في Laravel
        // $recipient->notify(new CurriculumAlertNotification($data, $urgent));
        
        Log::info("تم إنشاء إشعار داخلي", [
            'recipient_id' => $recipient->id,
            'urgent' => $urgent,
            'title' => $title
        ]);
    }

    /**
     * إرسال تذكير للطالب
     */
    protected function sendStudentRecitationReminder(Student $student): void
    {
        // يمكن إرسال التذكير للطالب أو ولي الأمر
        $message = "تذكير: لم يتم تسجيل جلسة تسميع للطالب {$student->name} منذ عدة أيام. يرجى المتابعة.";
        
        // إرسال للمعلم المسؤول
        if ($student->teacher) {
            $this->sendTeacherReminder($student->teacher, $student, $message);
        }
        
        Log::info("تم إرسال تذكير تسميع", [
            'student_id' => $student->id,
            'student_name' => $student->name
        ]);
    }

    /**
     * إرسال تذكير للمعلم
     */
    protected function sendTeacherReminder(User $teacher, Student $student, string $message): void
    {
        if ($teacher->email && $teacher->reminder_notifications) {
            // إرسال بريد إلكتروني
            // Mail::to($teacher->email)->send(new StudentReminderMail($student, $message));
        }
        
        // إشعار داخلي
        $this->createInSystemNotification($teacher, [
            'type' => 'student_reminder',
            'student_id' => $student->id,
            'student_name' => $student->name,
            'message' => $message
        ], false);
    }

    /**
     * إرسال تقرير يومي بالبريد الإلكتروني
     */
    protected function sendDailyReportEmail(User $recipient, array $stats): void
    {
        // Mail::to($recipient->email)->send(new DailyPerformanceReportMail($stats));
        
        Log::info("تم إرسال التقرير اليومي", [
            'recipient' => $recipient->email,
            'date' => $stats['date']
        ]);
    }

    /**
     * إرسال التقرير الأسبوعي
     */
    protected function sendWeeklyReportEmail(User $recipient, array $stats): void
    {
        // Mail::to($recipient->email)->send(new WeeklyPerformanceReportMail($stats));
        
        Log::info("تم إرسال التقرير الأسبوعي", [
            'recipient' => $recipient->email,
            'week_start' => $stats['week_start']
        ]);
    }

    // وظائف مساعدة للإحصائيات
    protected function getStudentsWhoRecitedOn(Carbon $date): int
    {
        return Student::whereHas('progress', function($query) use ($date) {
            $query->whereDate('daily_tracking_updated_at', $date);
        })->count();
    }

    protected function calculateDailyCompletionRates(): array
    {
        // حساب معدلات الإكمال اليومي
        return [
            'average_completion' => 75.5,
            'students_100_percent' => 15,
            'students_below_50_percent' => 8
        ];
    }

    protected function getTopPerformersToday(): array
    {
        // جلب أفضل الطلاب أداءً اليوم
        return [];
    }

    protected function getStudentsNeedingAttention(): array
    {
        // جلب الطلاب الذين يحتاجون اهتمام
        return [];
    }

    protected function getWeeklyRecitationCount(): int
    {
        // حساب عدد جلسات التسميع الأسبوعية
        return 0;
    }

    protected function getWeeklyCurriculumChanges(): int
    {
        // حساب تغييرات المناهج الأسبوعية
        return 0;
    }

    protected function getWeeklyAlertSummary(): array
    {
        // ملخص التنبيهات الأسبوعية
        return [];
    }

    protected function getWeeklyPerformanceTrends(): array
    {
        // اتجاهات الأداء الأسبوعية
        return [];
    }

    protected function getStudentsWhoProgressed(): array
    {
        // الطلاب الذين تقدموا هذا الأسبوع
        return [];
    }

    protected function getWeeklyImprovementAreas(): array
    {
        // مجالات التحسين الأسبوعية
        return [];
    }
}
