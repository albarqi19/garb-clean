<?php

namespace App\Services;

use App\Models\CurriculumPlan;
use App\Models\Student;
use App\Models\StudentProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProgressTrackingService
{
    /**
     * تتبع تقدم الطالب في التسميع
     */
    public function trackRecitationProgress(int $studentId, int $planId, array $progressData): bool
    {
        try {
            $student = Student::find($studentId);
            $plan = CurriculumPlan::find($planId);
            
            if (!$student || !$plan) {
                return false;
            }

            // إنشاء أو تحديث سجل التقدم
            $progress = StudentProgress::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'curriculum_plan_id' => $planId,
                ],
                [
                    'status' => $progressData['status'] ?? 'in_progress',
                    'completion_percentage' => $progressData['completion_percentage'] ?? 0,
                    'recitation_quality' => $progressData['recitation_quality'] ?? null,
                    'mistakes_count' => $progressData['mistakes_count'] ?? 0,
                    'notes' => $progressData['notes'] ?? null,
                    'completed_at' => $progressData['status'] === 'completed' ? now() : null,
                    'last_activity' => now(),
                    'teacher_feedback' => $progressData['teacher_feedback'] ?? null,
                    'review_required' => $progressData['review_required'] ?? false,
                ]
            );

            // إعادة ترتيب المنهج إذا لزم الأمر
            if ($progress->wasRecentlyCreated || $progress->wasChanged('status')) {
                $this->autoReorganizeCurriculum($studentId);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('خطأ في تتبع التقدم: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إعادة ترتيب المنهج تلقائياً بناء على التقدم الفعلي
     */
    public function autoReorganizeCurriculum(int $studentId): bool
    {
        try {
            $student = Student::find($studentId);
            if (!$student || !$student->curriculum_id) {
                return false;
            }

            // الحصول على جميع خطط المنهج
            $allPlans = CurriculumPlan::where('curriculum_id', $student->curriculum_id)
                ->orderBy('plan_number')
                ->get();

            // الحصول على تقدم الطالب
            $progressRecords = StudentProgress::where('student_id', $studentId)
                ->with('curriculumPlan')
                ->get()
                ->keyBy('curriculum_plan_id');

            // تصنيف الخطط
            $completedPlans = collect();
            $strugglingPlans = collect();
            $pendingPlans = collect();

            foreach ($allPlans as $plan) {
                $progress = $progressRecords->get($plan->id);
                
                if (!$progress) {
                    $pendingPlans->push($plan);
                } elseif ($progress->status === 'completed') {
                    $completedPlans->push($plan);
                } elseif ($progress->review_required || $progress->mistakes_count > 5) {
                    $strugglingPlans->push($plan);
                } else {
                    $pendingPlans->push($plan);
                }
            }

            // إعادة ترتيب الخطط
            $reorganizedPlans = $this->reorganizePlans($completedPlans, $strugglingPlans, $pendingPlans, $studentId);

            // تحديث ترقيم الخطط
            $this->updatePlanNumbers($reorganizedPlans);

            return true;
        } catch (\Exception $e) {
            \Log::error('خطأ في إعادة ترتيب المنهج: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إعادة ترتيب الخطط بناء على الحالة والأولوية
     */
    private function reorganizePlans(Collection $completed, Collection $struggling, Collection $pending, int $studentId): Collection
    {
        $reorganized = collect();
        $planNumber = 1;

        // 1. الخطط المكتملة (للمرجع)
        foreach ($completed as $plan) {
            $plan->plan_number = $planNumber++;
            $plan->reorganized_at = now();
            $plan->save();
            $reorganized->push($plan);
        }

        // 2. الخطط التي تحتاج مراجعة (أولوية عالية)
        foreach ($struggling as $plan) {
            $plan->plan_number = $planNumber++;
            $plan->reorganized_at = now();
            $plan->priority = 'high';
            $plan->save();
            $reorganized->push($plan);
        }

        // 3. الخطط المعلقة (ترتيب عادي)
        foreach ($pending as $plan) {
            $plan->plan_number = $planNumber++;
            $plan->reorganized_at = now();
            $plan->save();
            $reorganized->push($plan);
        }

        return $reorganized;
    }

    /**
     * تحديث ترقيم الخطط
     */
    private function updatePlanNumbers(Collection $plans): void
    {
        $planNumber = 1;
        foreach ($plans as $plan) {
            $plan->update(['plan_number' => $planNumber++]);
        }
    }

    /**
     * حساب إحصائيات التقدم للطالب
     */
    public function calculateProgressStats(int $studentId): array
    {
        $student = Student::find($studentId);
        if (!$student || !$student->curriculum_id) {
            return [];
        }

        $totalPlans = CurriculumPlan::where('curriculum_id', $student->curriculum_id)->count();
        
        $progressRecords = StudentProgress::where('student_id', $studentId)->get();
        
        $completedCount = $progressRecords->where('status', 'completed')->count();
        $inProgressCount = $progressRecords->where('status', 'in_progress')->count();
        $strugglingCount = $progressRecords->where('review_required', true)->count();
        
        $completionPercentage = $totalPlans > 0 ? round(($completedCount / $totalPlans) * 100, 2) : 0;
        
        $averageQuality = $progressRecords->where('recitation_quality', '!=', null)
            ->avg('recitation_quality') ?? 0;
        
        $totalMistakes = $progressRecords->sum('mistakes_count');
        
        return [
            'total_plans' => $totalPlans,
            'completed_count' => $completedCount,
            'in_progress_count' => $inProgressCount,
            'struggling_count' => $strugglingCount,
            'pending_count' => $totalPlans - $progressRecords->count(),
            'completion_percentage' => $completionPercentage,
            'average_quality' => round($averageQuality, 2),
            'total_mistakes' => $totalMistakes,
            'performance_trend' => $this->calculatePerformanceTrend($studentId),
        ];
    }

    /**
     * حساب اتجاه الأداء (تحسن/تراجع/ثابت)
     */
    private function calculatePerformanceTrend(int $studentId): string
    {
        $recentProgress = StudentProgress::where('student_id', $studentId)
            ->whereNotNull('recitation_quality')
            ->orderBy('last_activity', 'desc')
            ->limit(10)
            ->pluck('recitation_quality');
        
        if ($recentProgress->count() < 3) {
            return 'insufficient_data';
        }
        
        $recent = $recentProgress->take(5)->avg();
        $previous = $recentProgress->skip(5)->avg();
        
        $difference = $recent - $previous;
        
        if ($difference > 0.5) {
            return 'improving';
        } elseif ($difference < -0.5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * إنشاء خطة مراجعة مخصصة للطالب
     */
    public function generateCustomReviewPlan(int $studentId): Collection
    {
        $strugglingPlans = StudentProgress::where('student_id', $studentId)
            ->where(function ($query) {
                $query->where('review_required', true)
                      ->orWhere('mistakes_count', '>', 3)
                      ->orWhere('recitation_quality', '<', 7);
            })
            ->with('curriculumPlan')
            ->get();

        $reviewPlan = collect();
        
        foreach ($strugglingPlans as $progress) {
            $plan = $progress->curriculumPlan;
            
            // تحديد نوع المراجعة المطلوبة
            $reviewType = $this->determineReviewType($progress);
            
            $reviewPlan->push([
                'plan_id' => $plan->id,
                'plan_name' => $plan->title ?? $plan->name,
                'review_type' => $reviewType,
                'priority' => $this->calculateReviewPriority($progress),
                'estimated_time' => $this->estimateReviewTime($progress),
                'last_attempt' => $progress->last_activity,
                'mistakes_count' => $progress->mistakes_count,
                'quality_score' => $progress->recitation_quality,
                'surah_info' => [
                    'surah_number' => $plan->surah_number,
                    'start_verse' => $plan->start_verse,
                    'end_verse' => $plan->end_verse,
                    'formatted_content' => $plan->formatted_content,
                ],
            ]);
        }
        
        // ترتيب خطة المراجعة حسب الأولوية
        return $reviewPlan->sortByDesc('priority');
    }

    /**
     * تحديد نوع المراجعة المطلوبة
     */
    private function determineReviewType(StudentProgress $progress): string
    {
        if ($progress->mistakes_count > 10) {
            return 'intensive_review';
        } elseif ($progress->recitation_quality < 5) {
            return 'quality_improvement';
        } elseif ($progress->mistakes_count > 5) {
            return 'accuracy_focus';
        } else {
            return 'light_review';
        }
    }

    /**
     * حساب أولوية المراجعة
     */
    private function calculateReviewPriority(StudentProgress $progress): int
    {
        $priority = 0;
        
        // أولوية بناء على عدد الأخطاء
        $priority += min($progress->mistakes_count * 2, 20);
        
        // أولوية بناء على جودة التسميع
        $priority += (10 - ($progress->recitation_quality ?? 10)) * 3;
        
        // أولوية بناء على الوقت منذ آخر نشاط
        $daysSinceActivity = now()->diffInDays($progress->last_activity);
        $priority += min($daysSinceActivity, 10);
        
        return min($priority, 100);
    }

    /**
     * تقدير وقت المراجعة المطلوب
     */
    private function estimateReviewTime(StudentProgress $progress): int
    {
        $baseTime = 15; // دقيقة
        
        // زيادة الوقت حسب عدد الأخطاء
        $baseTime += min($progress->mistakes_count * 2, 30);
        
        // زيادة الوقت حسب ضعف جودة التسميع
        if ($progress->recitation_quality < 7) {
            $baseTime += (7 - $progress->recitation_quality) * 5;
        }
        
        return min($baseTime, 60);
    }

    /**
     * إنشاء تقرير تقدم شامل للطالب
     */
    public function generateProgressReport(int $studentId): array
    {
        $stats = $this->calculateProgressStats($studentId);
        $reviewPlan = $this->generateCustomReviewPlan($studentId);
        
        $student = Student::find($studentId);
        
        return [
            'student_info' => [
                'id' => $student->id,
                'name' => $student->name,
                'curriculum' => $student->curriculum->name ?? 'غير محدد',
            ],
            'progress_stats' => $stats,
            'custom_review_plan' => $reviewPlan->toArray(),
            'recommendations' => $this->generateRecommendations($stats, $reviewPlan),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * إنشاء توصيات للطالب والمعلم
     */
    private function generateRecommendations(array $stats, Collection $reviewPlan): array
    {
        $recommendations = [];
        
        // توصيات بناء على نسبة الإكمال
        if ($stats['completion_percentage'] < 50) {
            $recommendations[] = [
                'type' => 'completion',
                'message' => 'يُنصح بزيادة وتيرة الحفظ لتحسين نسبة الإكمال',
                'priority' => 'medium',
            ];
        }
        
        // توصيات بناء على جودة التسميع
        if ($stats['average_quality'] < 7) {
            $recommendations[] = [
                'type' => 'quality',
                'message' => 'يحتاج الطالب لتركيز أكبر على جودة التسميع وتحسين الأداء',
                'priority' => 'high',
            ];
        }
        
        // توصيات بناء على الأخطاء
        if ($stats['total_mistakes'] > 50) {
            $recommendations[] = [
                'type' => 'accuracy',
                'message' => 'يُنصح بتخصيص وقت إضافي للمراجعة وتقليل الأخطاء',
                'priority' => 'high',
            ];
        }
        
        // توصيات بناء على خطة المراجعة
        if ($reviewPlan->count() > 5) {
            $recommendations[] = [
                'type' => 'review',
                'message' => 'هناك عدد كبير من المواضع التي تحتاج مراجعة، يُنصح بتركيز الجهود على المراجعة',
                'priority' => 'high',
            ];
        }
        
        return $recommendations;
    }
}
