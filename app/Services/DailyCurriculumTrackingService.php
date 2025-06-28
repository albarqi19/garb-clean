<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Curriculum;
use App\Models\StudentProgress;
use App\Models\RecitationSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyCurriculumTrackingService
{
    /**
     * تحديد ما يجب على الطالب تسميعه غداً بناءً على المنهج الحالي
     */
    public function getNextDayRecitationContent($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $currentProgress = StudentProgress::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if (!$currentProgress) {
            return null;
        }

        $curriculum = Curriculum::find($currentProgress->curriculum_id);
        if (!$curriculum) {
            return null;
        }

        // حساب المحتوى التالي بناءً على آخر جلسة تسميع
        $lastSession = RecitationSession::where('student_id', $studentId)
            ->where('curriculum_id', $curriculum->id)
            ->orderBy('session_date', 'desc')
            ->first();

        $nextContent = $this->calculateNextContent($curriculum, $lastSession, $currentProgress);
        
        // تحديث حقل next_recitation_content في StudentProgress
        $currentProgress->update([
            'next_recitation_content' => $nextContent,
            'last_recitation_date' => $lastSession ? $lastSession->session_date : null
        ]);

        return $nextContent;
    }

    /**
     * عرض ما على الطالب تسميعه اليوم للمعلم
     */
    public function getTodayRecitationContent($studentId)
    {
        $currentProgress = StudentProgress::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if (!$currentProgress) {
            return null;
        }

        // إذا لم يكن هناك محتوى محدد لليوم، استخدم المحتوى التالي
        if (!$currentProgress->today_recitation_content) {
            $todayContent = $this->getNextDayRecitationContent($studentId);
            $currentProgress->update(['today_recitation_content' => $todayContent]);
            return $todayContent;
        }

        return $currentProgress->today_recitation_content;
    }

    /**
     * تحديث حالة التقدم في المنهج بعد كل جلسة
     */
    public function updateProgressAfterSession($sessionId)
    {
        $session = RecitationSession::find($sessionId);
        if (!$session) {
            return false;
        }

        $currentProgress = StudentProgress::where('student_id', $session->student_id)
            ->where('curriculum_id', $session->curriculum_id)
            ->where('is_active', true)
            ->first();

        if (!$currentProgress) {
            return false;
        }

        // حساب نسبة الإكمال
        $completionPercentage = $this->calculateCompletionPercentage($session->curriculum_id, $session->student_id);
        
        // تحديث البيانات
        $currentProgress->update([
            'completion_percentage' => $completionPercentage,
            'last_recitation_date' => $session->session_date,
            'updated_at' => now()
        ]);

        // تحديد المحتوى للغد
        $this->getNextDayRecitationContent($session->student_id);

        return true;
    }

    /**
     * حساب نسبة إكمال المنهج الحالي
     */
    public function calculateCompletionPercentage($curriculumId, $studentId)
    {
        $curriculum = Curriculum::find($curriculumId);
        if (!$curriculum) {
            return 0;
        }

        // حساب إجمالي الجلسات المكتملة
        $completedSessions = RecitationSession::where('student_id', $studentId)
            ->where('curriculum_id', $curriculumId)
            ->where('is_completed', true)
            ->count();

        // تقدير إجمالي الجلسات المطلوبة بناءً على نوع المنهج
        $totalExpectedSessions = $this->estimateTotalSessions($curriculum);

        if ($totalExpectedSessions == 0) {
            return 0;
        }

        $percentage = ($completedSessions / $totalExpectedSessions) * 100;
        
        // تحديد الحد الأقصى بـ 100%
        return min($percentage, 100);
    }

    /**
     * حساب المحتوى التالي بناءً على المنهج وآخر جلسة
     */
    private function calculateNextContent($curriculum, $lastSession, $currentProgress)
    {
        if (!$lastSession) {
            // إذا لم تكن هناك جلسات سابقة، ابدأ من البداية
            return $this->getInitialContent($curriculum);
        }

        // حساب المحتوى التالي بناءً على نوع المنهج
        switch ($curriculum->type) {
            case 'memorization':
                return $this->calculateNextMemorizationContent($curriculum, $lastSession, $currentProgress);
            case 'review':
                return $this->calculateNextReviewContent($curriculum, $lastSession, $currentProgress);
            case 'correction':
                return $this->calculateNextCorrectionContent($curriculum, $lastSession, $currentProgress);
            default:
                return $this->getStandardNextContent($curriculum, $lastSession);
        }
    }

    /**
     * الحصول على المحتوى الأولي للمنهج
     */
    private function getInitialContent($curriculum)
    {
        // تحليل محتوى المنهج للحصول على النقطة البداية
        $content = json_decode($curriculum->content, true);
        
        if (isset($content['start_point'])) {
            return $content['start_point'];
        }

        // افتراضي: ابدأ من السورة الأولى أو الجزء الأول
        return "بداية المنهج - " . $curriculum->name;
    }

    /**
     * حساب المحتوى التالي للحفظ
     */
    private function calculateNextMemorizationContent($curriculum, $lastSession, $currentProgress)
    {
        // منطق خاص بمناهج الحفظ
        $content = json_decode($curriculum->content, true);
        $lastContent = $lastSession->recitation_content ?? '';
        
        // تقدم تدريجي في الحفظ
        return "المتابعة من: " . $lastContent . " + آيات جديدة";
    }

    /**
     * حساب المحتوى التالي للمراجعة
     */
    private function calculateNextReviewContent($curriculum, $lastSession, $currentProgress)
    {
        // منطق خاص بمناهج المراجعة
        return "مراجعة ما تم حفظه سابقاً";
    }

    /**
     * حساب المحتوى التالي للتصحيح
     */
    private function calculateNextCorrectionContent($curriculum, $lastSession, $currentProgress)
    {
        // منطق خاص بمناهج التصحيح
        return "تصحيح الأخطاء المحددة في الجلسة السابقة";
    }

    /**
     * الحصول على المحتوى التالي العادي
     */
    private function getStandardNextContent($curriculum, $lastSession)
    {
        return "متابعة المنهج من النقطة التالية";
    }

    /**
     * تقدير إجمالي الجلسات المطلوبة للمنهج
     */
    private function estimateTotalSessions($curriculum)
    {
        // تقدير بناءً على نوع المنهج ومحتواه
        switch ($curriculum->type) {
            case 'memorization':
                return $curriculum->estimated_duration_days ?? 30;
            case 'review':
                return $curriculum->estimated_duration_days ?? 15;
            case 'correction':
                return $curriculum->estimated_duration_days ?? 10;
            default:
                return 20; // قيمة افتراضية
        }
    }

    /**
     * الحصول على ملخص التقدم اليومي للطالب
     */
    public function getDailyProgressSummary($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $currentProgress = StudentProgress::where('student_id', $studentId)
            ->where('is_active', true)
            ->with('curriculum')
            ->first();

        if (!$currentProgress) {
            return null;
        }

        return [
            'student_name' => $student->name,
            'current_curriculum' => $currentProgress->curriculum->name,
            'completion_percentage' => $currentProgress->completion_percentage ?? 0,
            'today_content' => $currentProgress->today_recitation_content,
            'next_content' => $currentProgress->next_recitation_content,
            'last_recitation_date' => $currentProgress->last_recitation_date,
            'curriculum_alerts' => $currentProgress->curriculum_alerts
        ];
    }

    /**
     * تحديث محتوى تسميع اليوم
     */
    public function updateTodayRecitationContent($studentId, $content)
    {
        $currentProgress = StudentProgress::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if ($currentProgress) {
            $currentProgress->update(['today_recitation_content' => $content]);
            return true;
        }

        return false;
    }

    /**
     * ربط جلسة التسميع بالمنهج الدراسي الحالي
     */
    public function trackCurriculumForSession($session)
    {
        try {
            $studentProgress = StudentProgress::where('student_id', $session->student_id)
                ->where('is_active', true)
                ->first();

            if ($studentProgress && !$session->curriculum_id) {
                $session->update(['curriculum_id' => $studentProgress->curriculum_id]);
                
                Log::info('Session linked to curriculum', [
                    'session_id' => $session->session_id,
                    'student_id' => $session->student_id,
                    'curriculum_id' => $studentProgress->curriculum_id
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to link session to curriculum', [
                'session_id' => $session->session_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * الحصول على المنهج اليومي للطالب
     */
    public function getDailyCurriculum($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        // البحث عن منهج الطالب النشط من جدول student_curricula
        $studentCurriculum = \App\Models\StudentCurriculum::where('student_id', $studentId)
            ->where('is_active', true)
            ->with(['curriculum', 'student'])
            ->first();

        if (!$studentCurriculum) {
            return null;
        }

        // حساب المحتوى اليومي
        $dailyContent = [
            'student_id' => $studentId,
            'student_name' => $student->name,
            'curriculum_name' => $studentCurriculum->curriculum->name,
            'current_page' => $studentCurriculum->current_page ?? 1,
            'current_surah' => $studentCurriculum->current_surah ?? 'الفاتحة',
            'current_ayah' => $studentCurriculum->current_ayah ?? 1,
            'daily_memorization_pages' => $studentCurriculum->daily_memorization_pages ?? 1,
            'daily_minor_review_pages' => $studentCurriculum->daily_minor_review_pages ?? 2,
            'daily_major_review_pages' => $studentCurriculum->daily_major_review_pages ?? 5,
            'next_day_content' => $this->calculateNextDayContent($studentCurriculum),
            'progress_percentage' => $this->calculateProgressPercentage($studentCurriculum->current_page ?? 1),
            'status' => $studentCurriculum->status ?? 'قيد التنفيذ'
        ];

        return $dailyContent;
    }

    /**
     * حساب محتوى اليوم التالي
     */
    private function calculateNextDayContent($studentCurriculum)
    {
        $currentPage = $studentCurriculum->current_page ?? 1;
        $dailyPages = $studentCurriculum->daily_memorization_pages ?? 1;
        
        return [
            'memorization' => "حفظ " . $dailyPages . " صفحة من الصفحة " . ($currentPage + 1),
            'minor_review' => "مراجعة صغرى: " . ($studentCurriculum->daily_minor_review_pages ?? 2) . " صفحة",
            'major_review' => "مراجعة كبرى: " . ($studentCurriculum->daily_major_review_pages ?? 5) . " صفحة"
        ];
    }

    /**
     * حساب نسبة التقدم
     */
    private function calculateProgressPercentage($currentPage)
    {
        $totalPages = 604; // إجمالي صفحات المصحف
        return round(($currentPage / $totalPages) * 100, 1);
    }
}
