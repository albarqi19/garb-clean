<?php

namespace App\Services;

use App\Models\StudentCurriculumProgress;
use App\Models\StudentCurriculum;
use App\Models\CurriculumPlan;
use App\Models\Student;
use Carbon\Carbon;

class AdvancedProgressTrackingService
{
    /**
     * تحديث تقدم الطالب وإعادة ترتيب المنهج تلقائياً
     */
    public static function updateStudentProgress(
        int $studentId,
        int $planId,
        string $recitationStatus,
        int $mistakesCount = 0,
        ?string $teacherNotes = null
    ): array {
        $student = Student::find($studentId);
        $plan = CurriculumPlan::find($planId);
        
        if (!$student || !$plan) {
            return ['success' => false, 'message' => 'بيانات غير صحيحة'];
        }

        // البحث عن السجل الحالي للطالب في هذا المنهج
        $studentCurriculum = StudentCurriculum::where('student_id', $studentId)
            ->where('curriculum_id', $plan->curriculum_id)
            ->first();

        if (!$studentCurriculum) {
            return ['success' => false, 'message' => 'الطالب غير مسجل في هذا المنهج'];
        }

        // تحديث أو إنشاء سجل التقدم
        $progress = StudentCurriculumProgress::updateOrCreate(
            [
                'student_curriculum_id' => $studentCurriculum->id,
                'curriculum_plan_id' => $planId,
            ],
            [
                'recitation_status' => $recitationStatus,
                'mistakes_count' => $mistakesCount,
                'teacher_notes' => $teacherNotes,
                'completed_at' => $recitationStatus === 'مكتمل' ? now() : null,
                'updated_at' => now(),
            ]
        );

        // حساب التقدم الإجمالي وإعادة الترتيب
        $reorderingResult = self::autoReorderCurriculum($studentCurriculum);
        
        // تحديث إحصائيات الطالب
        self::updateStudentStatistics($studentCurriculum);

        return [
            'success' => true,
            'progress' => $progress,
            'reordering_applied' => $reorderingResult['reordering_applied'],
            'next_plans' => $reorderingResult['next_plans'],
            'message' => 'تم تحديث التقدم بنجاح'
        ];
    }

    /**
     * إعادة ترتيب المنهج تلقائياً بناء على التقدم الفعلي
     */
    public static function autoReorderCurriculum(StudentCurriculum $studentCurriculum): array
    {
        $student = $studentCurriculum->student;
        $curriculum = $studentCurriculum->curriculum;
        
        // الحصول على جميع الخطط والتقدم
        $allPlans = $curriculum->plans()->orderBy('level.level_order')->orderBy('created_at')->get();
        $progressRecords = $studentCurriculum->progress()->get()->keyBy('curriculum_plan_id');
        
        // تحليل الأداء
        $performanceAnalysis = self::analyzeStudentPerformance($progressRecords);
        
        $reorderingApplied = false;
        $recommendedPlans = [];
        
        // تحديد الخطط التالية بناء على الأداء
        if ($performanceAnalysis['difficulty_level'] === 'high') {
            // طالب يواجه صعوبة - تقليل الحمولة وزيادة المراجعة
            $recommendedPlans = self::getEasierPath($allPlans, $progressRecords);
            $reorderingApplied = true;
        } elseif ($performanceAnalysis['difficulty_level'] === 'low') {
            // طالب متميز - تسريع المنهج
            $recommendedPlans = self::getAcceleratedPath($allPlans, $progressRecords);
            $reorderingApplied = true;
        } else {
            // أداء طبيعي - المتابعة حسب الخطة الأصلية
            $recommendedPlans = self::getStandardPath($allPlans, $progressRecords);
        }

        // حفظ التوصيات في قاعدة البيانات
        if ($reorderingApplied) {
            self::saveRecommendations($studentCurriculum->id, $recommendedPlans, $performanceAnalysis);
        }

        return [
            'reordering_applied' => $reorderingApplied,
            'performance_analysis' => $performanceAnalysis,
            'next_plans' => collect($recommendedPlans)->take(5)->toArray(),
            'recommendations_count' => count($recommendedPlans)
        ];
    }

    /**
     * تحليل أداء الطالب
     */
    private static function analyzeStudentPerformance($progressRecords): array
    {
        $totalPlans = $progressRecords->count();
        $completedPlans = $progressRecords->where('recitation_status', 'مكتمل')->count();
        $failedPlans = $progressRecords->where('recitation_status', 'راسب')->count();
        $averageMistakes = $progressRecords->avg('mistakes_count') ?? 0;
        
        // حساب معدل النجاح
        $successRate = $totalPlans > 0 ? ($completedPlans / $totalPlans) * 100 : 0;
        
        // تحديد مستوى الصعوبة
        $difficultyLevel = 'normal';
        if ($successRate < 60 || $averageMistakes > 5) {
            $difficultyLevel = 'high';
        } elseif ($successRate > 85 && $averageMistakes < 2) {
            $difficultyLevel = 'low';
        }

        // حساب الوقت المتوسط للإكمال
        $completedRecords = $progressRecords->where('recitation_status', 'مكتمل')
                                          ->where('completed_at', '!=', null);
        
        $averageCompletionTime = 0;
        if ($completedRecords->count() > 0) {
            $totalTime = 0;
            foreach ($completedRecords as $record) {
                $planCreated = Carbon::parse($record->created_at);
                $planCompleted = Carbon::parse($record->completed_at);
                $totalTime += $planCreated->diffInDays($planCompleted);
            }
            $averageCompletionTime = $totalTime / $completedRecords->count();
        }

        return [
            'total_plans' => $totalPlans,
            'completed_plans' => $completedPlans,
            'failed_plans' => $failedPlans,
            'success_rate' => round($successRate, 2),
            'average_mistakes' => round($averageMistakes, 2),
            'difficulty_level' => $difficultyLevel,
            'average_completion_days' => round($averageCompletionTime, 1),
            'recommendation' => self::getPerformanceRecommendation($difficultyLevel, $successRate, $averageMistakes)
        ];
    }

    /**
     * الحصول على توصية بناء على الأداء
     */
    private static function getPerformanceRecommendation(string $difficultyLevel, float $successRate, float $averageMistakes): string
    {
        return match ($difficultyLevel) {
            'high' => "الطالب يحتاج إلى دعم إضافي. معدل النجاح: {$successRate}%، متوسط الأخطاء: {$averageMistakes}",
            'low' => "طالب متميز يمكن تسريع وتيرة التعلم. معدل النجاح: {$successRate}%، متوسط الأخطاء: {$averageMistakes}",
            default => "أداء طبيعي، يمكن المتابعة حسب الخطة المعتادة. معدل النجاح: {$successRate}%"
        };
    }

    /**
     * الحصول على مسار أسهل للطلاب ذوي الأداء المنخفض
     */
    private static function getEasierPath($allPlans, $progressRecords): array
    {
        $incompletePlans = $allPlans->filter(function ($plan) use ($progressRecords) {
            $progress = $progressRecords->get($plan->id);
            return !$progress || $progress->recitation_status !== 'مكتمل';
        });

        // ترتيب الخطط بحيث نبدأ بالمراجعة ثم الدروس الجديدة
        $reviewPlans = $incompletePlans->where('plan_type', 'المراجعة الصغرى')->take(3);
        $lessonPlans = $incompletePlans->where('plan_type', 'الدرس')->take(2);

        return $reviewPlans->concat($lessonPlans)->toArray();
    }

    /**
     * الحصول على مسار متسارع للطلاب المتميزين
     */
    private static function getAcceleratedPath($allPlans, $progressRecords): array
    {
        $incompletePlans = $allPlans->filter(function ($plan) use ($progressRecords) {
            $progress = $progressRecords->get($plan->id);
            return !$progress || $progress->recitation_status !== 'مكتمل';
        });

        // التركيز على الدروس الجديدة أكثر من المراجعة
        return $incompletePlans->where('plan_type', 'الدرس')->take(5)->toArray();
    }

    /**
     * الحصول على المسار القياسي
     */
    private static function getStandardPath($allPlans, $progressRecords): array
    {
        $incompletePlans = $allPlans->filter(function ($plan) use ($progressRecords) {
            $progress = $progressRecords->get($plan->id);
            return !$progress || $progress->recitation_status !== 'مكتمل';
        });

        return $incompletePlans->take(5)->toArray();
    }

    /**
     * حفظ التوصيات في قاعدة البيانات
     */
    private static function saveRecommendations(int $studentCurriculumId, array $recommendedPlans, array $performanceAnalysis): void
    {
        // يمكن إنشاء جدول منفصل للتوصيات أو حفظها في حقل JSON
        $recommendations = [
            'plans' => collect($recommendedPlans)->pluck('id')->toArray(),
            'analysis' => $performanceAnalysis,
            'created_at' => now()->toDateTimeString(),
        ];

        // حفظ في حقل JSON في جدول StudentCurriculum
        StudentCurriculum::where('id', $studentCurriculumId)
            ->update(['recommendations' => json_encode($recommendations)]);
    }

    /**
     * تحديث إحصائيات الطالب
     */
    private static function updateStudentStatistics(StudentCurriculum $studentCurriculum): void
    {
        $totalPlans = $studentCurriculum->curriculum->plans()->count();
        $completedPlans = $studentCurriculum->progress()
            ->where('recitation_status', 'مكتمل')
            ->count();

        $completionPercentage = $totalPlans > 0 ? ($completedPlans / $totalPlans) * 100 : 0;

        $studentCurriculum->update([
            'completion_percentage' => round($completionPercentage, 2),
            'completed_plans_count' => $completedPlans,
            'total_plans_count' => $totalPlans,
            'last_activity_date' => now(),
        ]);
    }

    /**
     * الحصول على تقرير تقدم مفصل للطالب
     */
    public static function getDetailedProgressReport(int $studentId, int $curriculumId): array
    {
        $studentCurriculum = StudentCurriculum::where('student_id', $studentId)
            ->where('curriculum_id', $curriculumId)
            ->with(['progress.plan', 'curriculum.plans'])
            ->first();

        if (!$studentCurriculum) {
            return ['error' => 'بيانات غير موجودة'];
        }

        $progressRecords = $studentCurriculum->progress->keyBy('curriculum_plan_id');
        $performanceAnalysis = self::analyzeStudentPerformance($progressRecords);

        return [
            'student_curriculum' => $studentCurriculum,
            'performance_analysis' => $performanceAnalysis,
            'progress_by_type' => [
                'lessons' => $progressRecords->filter(fn($p) => $p->plan->plan_type === 'الدرس'),
                'minor_reviews' => $progressRecords->filter(fn($p) => $p->plan->plan_type === 'المراجعة الصغرى'),
                'major_reviews' => $progressRecords->filter(fn($p) => $p->plan->plan_type === 'المراجعة الكبرى'),
            ],
            'recommendations' => json_decode($studentCurriculum->recommendations ?? '{}', true),
            'next_plans' => self::getStandardPath($studentCurriculum->curriculum->plans, $progressRecords)
        ];
    }
}
