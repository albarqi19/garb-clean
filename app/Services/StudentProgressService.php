<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentProgress;
use App\Models\CurriculumPlan;
use App\Models\Curriculum;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentProgressService
{
    /**
     * تسجيل تقدم الطالب في خطة معينة
     */
    public function recordProgress(
        Student $student,
        CurriculumPlan $plan,
        string $activityType,
        string $status,
        int $score = null,
        array $notes = null,
        int $recordedBy = null
    ): StudentProgress {
        $progress = StudentProgress::create([
            'student_id' => $student->id,
            'curriculum_plan_id' => $plan->id,
            'activity_type' => $activityType,
            'status' => $status,
            'score' => $score,
            'notes' => $notes,
            'completed_at' => $status === 'completed' ? now() : null,
            'recorded_by' => $recordedBy ?? auth()->id(),
        ]);

        // إعادة ترتيب المنهج إذا لزم الأمر
        $this->adjustCurriculumIfNeeded($student, $plan, $status, $score);

        return $progress;
    }

    /**
     * تسجيل تسميع للطالب
     */
    public function recordRecitation(
        Student $student,
        CurriculumPlan $plan,
        int $score,
        array $mistakes = [],
        string $notes = null,
        int $recordedBy = null
    ): StudentProgress {
        $status = $this->determineRecitationStatus($score);
        
        $progressNotes = [
            'mistakes' => $mistakes,
            'general_notes' => $notes,
            'recitation_score' => $score,
            'recitation_date' => now()->toDateString(),
        ];

        $progress = $this->recordProgress(
            $student,
            $plan,
            'recitation',
            $status,
            $score,
            $progressNotes,
            $recordedBy
        );

        // إضافة توصيات بناء على الأداء
        $this->addPerformanceRecommendations($student, $plan, $score, $mistakes);

        return $progress;
    }

    /**
     * تسجيل حفظ جديد للطالب
     */
    public function recordMemorization(
        Student $student,
        CurriculumPlan $plan,
        int $score,
        string $notes = null,
        int $recordedBy = null
    ): StudentProgress {
        $status = $this->determineMemorizationStatus($score);
        
        $progressNotes = [
            'general_notes' => $notes,
            'memorization_score' => $score,
            'memorization_date' => now()->toDateString(),
        ];

        return $this->recordProgress(
            $student,
            $plan,
            'memorization',
            $status,
            $score,
            $progressNotes,
            $recordedBy
        );
    }

    /**
     * تسجيل مراجعة للطالب
     */
    public function recordReview(
        Student $student,
        CurriculumPlan $plan,
        int $score,
        array $weakPoints = [],
        string $notes = null,
        int $recordedBy = null
    ): StudentProgress {
        $status = $this->determineReviewStatus($score);
        
        $progressNotes = [
            'weak_points' => $weakPoints,
            'general_notes' => $notes,
            'review_score' => $score,
            'review_date' => now()->toDateString(),
        ];

        return $this->recordProgress(
            $student,
            $plan,
            'review',
            $status,
            $score,
            $progressNotes,
            $recordedBy
        );
    }

    /**
     * إعادة ترتيب المنهج بناء على تقدم الطالب
     */
    private function adjustCurriculumIfNeeded(
        Student $student,
        CurriculumPlan $plan,
        string $status,
        int $score = null
    ): void {
        // إذا فشل الطالب في خطة معينة
        if ($status === 'failed' || ($score !== null && $score < 60)) {
            $this->addReinforcementPlans($student, $plan);
        }

        // إذا تفوق الطالب بشكل ممتاز
        if ($status === 'excellent' || ($score !== null && $score >= 95)) {
            $this->accelerateProgress($student, $plan);
        }

        // إذا كان الأداء ضعيف متكرر
        $this->checkForRepeatedWeakness($student, $plan);
    }

    /**
     * إضافة خطط تعزيزية للطالب
     */
    private function addReinforcementPlans(Student $student, CurriculumPlan $originalPlan): void
    {
        // إنشاء خطة مراجعة إضافية
        $reinforcementPlan = CurriculumPlan::create([
            'curriculum_id' => $originalPlan->curriculum_id,
            'curriculum_level_id' => $originalPlan->curriculum_level_id,
            'plan_number' => $originalPlan->plan_number . '.1',
            'title' => 'تعزيز: ' . $originalPlan->title,
            'description' => 'خطة تعزيزية لتحسين الأداء في: ' . $originalPlan->title,
            'plan_type' => 'المراجعة الصغرى',
            'content_type' => $originalPlan->content_type,
            'surah_number' => $originalPlan->surah_number,
            'start_verse' => $originalPlan->start_verse,
            'end_verse' => $originalPlan->end_verse,
            'calculated_verses' => $originalPlan->calculated_verses,
            'formatted_content' => $originalPlan->formatted_content,
            'content' => $originalPlan->content,
            'duration_minutes' => ceil($originalPlan->duration_minutes * 0.7), // وقت أقل للمراجعة
            'status' => 'pending',
            'is_reinforcement' => true,
        ]);

        // تسجيل أن هذه خطة تم إضافتها تلقائياً
        StudentProgress::create([
            'student_id' => $student->id,
            'curriculum_plan_id' => $reinforcementPlan->id,
            'activity_type' => 'auto_adjustment',
            'status' => 'pending',
            'notes' => [
                'adjustment_type' => 'reinforcement',
                'original_plan_id' => $originalPlan->id,
                'reason' => 'ضعف في الأداء يتطلب تعزيز',
                'created_at' => now()->toDateTimeString(),
            ],
            'recorded_by' => null, // تلقائي
        ]);
    }

    /**
     * تسريع التقدم للطلاب المتفوقين
     */
    private function accelerateProgress(Student $student, CurriculumPlan $plan): void
    {
        // البحث عن الخطة التالية
        $nextPlan = CurriculumPlan::where('curriculum_id', $plan->curriculum_id)
            ->where('plan_number', '>', $plan->plan_number)
            ->orderBy('plan_number')
            ->first();

        if ($nextPlan && $nextPlan->plan_type === 'المراجعة الصغرى') {
            // تخطي المراجعة الصغرى للطلاب المتفوقين
            StudentProgress::create([
                'student_id' => $student->id,
                'curriculum_plan_id' => $nextPlan->id,
                'activity_type' => 'auto_adjustment',
                'status' => 'skipped',
                'notes' => [
                    'adjustment_type' => 'acceleration',
                    'reason' => 'تفوق في الأداء يسمح بتخطي المراجعة',
                    'original_plan_id' => $plan->id,
                    'created_at' => now()->toDateTimeString(),
                ],
                'recorded_by' => null,
            ]);
        }
    }

    /**
     * فحص الضعف المتكرر
     */
    private function checkForRepeatedWeakness(Student $student, CurriculumPlan $plan): void
    {
        // فحص آخر 5 محاولات للطالب
        $recentProgress = StudentProgress::where('student_id', $student->id)
            ->where('activity_type', 'recitation')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $failedCount = $recentProgress->where('status', 'failed')->count();
        $lowScoreCount = $recentProgress->where('score', '<', 70)->count();

        // إذا فشل 3 مرات من آخر 5 محاولات
        if ($failedCount >= 3 || $lowScoreCount >= 4) {
            $this->createIntensiveReviewPlan($student, $plan);
        }
    }

    /**
     * إنشاء خطة مراجعة مكثفة
     */
    private function createIntensiveReviewPlan(Student $student, CurriculumPlan $plan): void
    {
        // إنشاء خطة مراجعة مكثفة
        $intensivePlan = CurriculumPlan::create([
            'curriculum_id' => $plan->curriculum_id,
            'curriculum_level_id' => $plan->curriculum_level_id,
            'plan_number' => $plan->plan_number . '.intensive',
            'title' => 'مراجعة مكثفة: ' . $plan->title,
            'description' => 'خطة مراجعة مكثفة بسبب الضعف المتكرر في: ' . $plan->title,
            'plan_type' => 'المراجعة الكبرى',
            'content_type' => $plan->content_type,
            'surah_number' => $plan->surah_number,
            'start_verse' => $plan->start_verse,
            'end_verse' => $plan->end_verse,
            'calculated_verses' => $plan->calculated_verses,
            'formatted_content' => $plan->formatted_content,
            'content' => $plan->content,
            'duration_minutes' => $plan->duration_minutes * 2, // وقت مضاعف
            'status' => 'pending',
            'is_intensive' => true,
        ]);

        // إشعار المعلم
        $this->notifyTeacherAboutIntensiveReview($student, $intensivePlan);
    }

    /**
     * تحديد حالة التسميع بناء على الدرجة
     */
    private function determineRecitationStatus(int $score): string
    {
        return match (true) {
            $score >= 95 => 'excellent',
            $score >= 85 => 'very_good',
            $score >= 75 => 'good',
            $score >= 60 => 'acceptable',
            default => 'failed'
        };
    }

    /**
     * تحديد حالة الحفظ بناء على الدرجة
     */
    private function determineMemorizationStatus(int $score): string
    {
        return match (true) {
            $score >= 90 => 'mastered',
            $score >= 80 => 'memorized',
            $score >= 70 => 'partial',
            default => 'needs_work'
        };
    }

    /**
     * تحديد حالة المراجعة بناء على الدرجة
     */
    private function determineReviewStatus(int $score): string
    {
        return match (true) {
            $score >= 95 => 'excellent',
            $score >= 85 => 'strong',
            $score >= 75 => 'good',
            $score >= 65 => 'adequate',
            default => 'weak'
        };
    }

    /**
     * إضافة توصيات بناء على الأداء
     */
    private function addPerformanceRecommendations(
        Student $student,
        CurriculumPlan $plan,
        int $score,
        array $mistakes
    ): void {
        $recommendations = [];

        // توصيات بناء على نوع الأخطاء
        if (in_array('تجويد', $mistakes)) {
            $recommendations[] = 'التركيز على قواعد التجويد';
        }
        
        if (in_array('حفظ', $mistakes)) {
            $recommendations[] = 'مراجعة إضافية للحفظ';
        }

        if (in_array('طلاقة', $mistakes)) {
            $recommendations[] = 'تحسين الطلاقة في القراءة';
        }

        // توصيات بناء على الدرجة
        if ($score < 70) {
            $recommendations[] = 'جلسة تقوية فردية مع المعلم';
        } elseif ($score >= 95) {
            $recommendations[] = 'الاستعداد للانتقال للمرحلة التالية';
        }

        // حفظ التوصيات
        if (!empty($recommendations)) {
            StudentProgress::create([
                'student_id' => $student->id,
                'curriculum_plan_id' => $plan->id,
                'activity_type' => 'recommendation',
                'status' => 'pending',
                'notes' => [
                    'recommendations' => $recommendations,
                    'based_on_score' => $score,
                    'based_on_mistakes' => $mistakes,
                    'created_at' => now()->toDateTimeString(),
                ],
                'recorded_by' => null,
            ]);
        }
    }

    /**
     * إشعار المعلم بالمراجعة المكثفة
     */
    private function notifyTeacherAboutIntensiveReview(Student $student, CurriculumPlan $intensivePlan): void
    {
        // يمكن تطوير نظام إشعارات هنا
        \Log::info("تم إنشاء خطة مراجعة مكثفة للطالب {$student->name} في الخطة {$intensivePlan->title}");
    }

    /**
     * الحصول على تقرير تقدم الطالب
     */
    public function getStudentProgressReport(Student $student, int $daysBack = 30): array
    {
        $progress = StudentProgress::where('student_id', $student->id)
            ->where('created_at', '>=', Carbon::now()->subDays($daysBack))
            ->with('curriculumPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_activities' => $progress->count(),
            'completed' => $progress->where('status', 'completed')->count(),
            'failed' => $progress->where('status', 'failed')->count(),
            'average_score' => $progress->whereNotNull('score')->avg('score'),
            'recitations_count' => $progress->where('activity_type', 'recitation')->count(),
            'memorizations_count' => $progress->where('activity_type', 'memorization')->count(),
            'reviews_count' => $progress->where('activity_type', 'review')->count(),
            'adjustments_made' => $progress->where('activity_type', 'auto_adjustment')->count(),
        ];

        return [
            'student' => $student,
            'progress' => $progress,
            'statistics' => $stats,
            'period' => $daysBack,
        ];
    }

    /**
     * الحصول على الخطط التالية المُوصى بها للطالب
     */
    public function getRecommendedNextPlans(Student $student, int $limit = 5): Collection
    {
        // الحصول على آخر خطة مكتملة
        $lastCompletedPlan = StudentProgress::where('student_id', $student->id)
            ->where('status', 'completed')
            ->with('curriculumPlan')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastCompletedPlan) {
            // إذا لم يكمل أي خطة، ابدأ من الأولى
            return CurriculumPlan::orderBy('plan_number')
                ->limit($limit)
                ->get();
        }

        // البحث عن الخطط التالية
        $curriculum = $lastCompletedPlan->curriculumPlan->curriculum;
        
        return CurriculumPlan::where('curriculum_id', $curriculum->id)
            ->where('plan_number', '>', $lastCompletedPlan->curriculumPlan->plan_number)
            ->whereNotIn('id', function ($query) use ($student) {
                $query->select('curriculum_plan_id')
                    ->from('student_progress')
                    ->where('student_id', $student->id)
                    ->where('status', 'completed');
            })
            ->orderBy('plan_number')
            ->limit($limit)
            ->get();
    }
}
