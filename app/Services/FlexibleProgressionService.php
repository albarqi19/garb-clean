<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Curriculum;
use App\Models\StudentProgress;
use App\Models\CurriculumAlert;
use App\Models\RecitationSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * خدمة آلية الانتقال المرن
 * تدير عملية الانتقال بين المناهج بناءً على أداء الطلاب والمعايير المرنة
 */
class FlexibleProgressionService
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
     * تقييم شامل لاستعداد الطالب للانتقال
     */
    public function evaluateProgressionReadiness(Student $student): array
    {
        $progress = $student->progress;
        if (!$progress) {
            return [
                'ready' => false,
                'reason' => 'لا يوجد تقدم مسجل للطالب',
                'score' => 0
            ];
        }

        // جمع جميع المعايير للتقييم
        $criteria = $this->gatherProgressionCriteria($student);
        
        // حساب نقاط الاستعداد
        $readinessScore = $this->calculateReadinessScore($criteria);
        
        // تحديد مستوى الاستعداد
        $readinessLevel = $this->determineReadinessLevel($readinessScore);
        
        Log::info("تقييم استعداد الطالب للانتقال", [
            'student_id' => $student->id,
            'score' => $readinessScore['total'],
            'level' => $readinessLevel['level']
        ]);

        return [
            'ready' => $readinessLevel['ready'],
            'level' => $readinessLevel['level'],
            'score' => $readinessScore['total'],
            'criteria' => $criteria,
            'detailed_scores' => $readinessScore,
            'recommendations' => $readinessLevel['recommendations'],
            'next_steps' => $this->getNextSteps($readinessLevel, $student)
        ];
    }

    /**
     * جمع معايير التقييم للانتقال
     */
    protected function gatherProgressionCriteria(Student $student): array
    {
        $progress = $student->progress;
        $twoWeeksAgo = Carbon::now()->subWeeks(2);
        
        // جلسات التسميع الحديثة
        $recentSessions = RecitationSession::where('student_id', $student->id)
            ->where('created_at', '>=', $twoWeeksAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        // معايير الأداء الأساسية
        $basicPerformance = [
            'completion_percentage' => $progress->completion_percentage ?? 0,
            'current_page' => $progress->current_page ?? 0,
            'verses_memorized' => $progress->verses_memorized ?? 0,
            'days_in_curriculum' => $this->getDaysInCurrentCurriculum($progress)
        ];

        // معايير جلسات التسميع
        $sessionPerformance = [
            'total_sessions' => $recentSessions->count(),
            'completed_sessions' => $recentSessions->where('status', 'completed')->count(),
            'average_score' => $recentSessions->where('status', 'completed')->avg('score') ?? 0,
            'consistency_rate' => $this->calculateSessionConsistency($recentSessions),
            'improvement_trend' => $this->calculateImprovementTrend($recentSessions)
        ];

        // معايير التتبع اليومي
        $dailyTracking = $this->getDailyTrackingCriteria($student);
        
        // معايير الجودة والإتقان
        $qualityCriteria = $this->getQualityCriteria($recentSessions);

        return [
            'basic_performance' => $basicPerformance,
            'session_performance' => $sessionPerformance,
            'daily_tracking' => $dailyTracking,
            'quality_criteria' => $qualityCriteria
        ];
    }

    /**
     * حساب نقاط الاستعداد لكل معيار
     */
    protected function calculateReadinessScore(array $criteria): array
    {
        $scores = [
            'curriculum_completion' => 0,
            'session_performance' => 0,
            'daily_consistency' => 0,
            'quality_mastery' => 0,
            'time_investment' => 0
        ];

        // نقاط إكمال المنهج (25 نقطة)
        $completionRate = $criteria['basic_performance']['completion_percentage'];
        if ($completionRate >= 90) {
            $scores['curriculum_completion'] = 25;
        } elseif ($completionRate >= 80) {
            $scores['curriculum_completion'] = 20;
        } elseif ($completionRate >= 70) {
            $scores['curriculum_completion'] = 15;
        } elseif ($completionRate >= 60) {
            $scores['curriculum_completion'] = 10;
        }

        // نقاط أداء الجلسات (25 نقطة)
        $sessionPerf = $criteria['session_performance'];
        $sessionScore = 0;
        
        if ($sessionPerf['average_score'] >= 85) $sessionScore += 10;
        elseif ($sessionPerf['average_score'] >= 75) $sessionScore += 8;
        elseif ($sessionPerf['average_score'] >= 65) $sessionScore += 6;
        
        if ($sessionPerf['consistency_rate'] >= 80) $sessionScore += 8;
        elseif ($sessionPerf['consistency_rate'] >= 70) $sessionScore += 6;
        elseif ($sessionPerf['consistency_rate'] >= 60) $sessionScore += 4;
        
        if ($sessionPerf['improvement_trend'] === 'improving') $sessionScore += 7;
        elseif ($sessionPerf['improvement_trend'] === 'stable') $sessionScore += 4;
        
        $scores['session_performance'] = min($sessionScore, 25);

        // نقاط الانتظام اليومي (20 نقطة)
        $dailyPerf = $criteria['daily_tracking'];
        if ($dailyPerf['consistency_score'] >= 90) {
            $scores['daily_consistency'] = 20;
        } elseif ($dailyPerf['consistency_score'] >= 80) {
            $scores['daily_consistency'] = 16;
        } elseif ($dailyPerf['consistency_score'] >= 70) {
            $scores['daily_consistency'] = 12;
        } elseif ($dailyPerf['consistency_score'] >= 60) {
            $scores['daily_consistency'] = 8;
        }

        // نقاط الجودة والإتقان (20 نقطة)
        $qualityPerf = $criteria['quality_criteria'];
        $qualityScore = 0;
        
        if ($qualityPerf['error_rate'] <= 2) $qualityScore += 8;
        elseif ($qualityPerf['error_rate'] <= 4) $qualityScore += 6;
        elseif ($qualityPerf['error_rate'] <= 6) $qualityScore += 4;
        
        if ($qualityPerf['mastery_level'] >= 85) $qualityScore += 8;
        elseif ($qualityPerf['mastery_level'] >= 75) $qualityScore += 6;
        elseif ($qualityPerf['mastery_level'] >= 65) $qualityScore += 4;
        
        if ($qualityPerf['retention_rate'] >= 90) $qualityScore += 4;
        elseif ($qualityPerf['retention_rate'] >= 80) $qualityScore += 3;
        elseif ($qualityPerf['retention_rate'] >= 70) $qualityScore += 2;
        
        $scores['quality_mastery'] = min($qualityScore, 20);

        // نقاط الاستثمار الزمني (10 نقاط)
        $daysInCurriculum = $criteria['basic_performance']['days_in_curriculum'];
        if ($daysInCurriculum >= 30 && $daysInCurriculum <= 90) {
            $scores['time_investment'] = 10;
        } elseif ($daysInCurriculum >= 20 && $daysInCurriculum <= 120) {
            $scores['time_investment'] = 8;
        } elseif ($daysInCurriculum >= 15) {
            $scores['time_investment'] = 6;
        }

        $scores['total'] = array_sum($scores);
        return $scores;
    }

    /**
     * تحديد مستوى الاستعداد بناءً على النقاط
     */
    protected function determineReadinessLevel(array $scores): array
    {
        $total = $scores['total'];
        
        if ($total >= 85) {
            return [
                'ready' => true,
                'level' => 'excellent',
                'confidence' => 'عالية جداً',
                'recommendations' => [
                    'الطالب مستعد تماماً للانتقال للمستوى التالي',
                    'يمكن النظر في تحديات إضافية',
                    'الانتقال الفوري مُوصى به'
                ]
            ];
        } elseif ($total >= 75) {
            return [
                'ready' => true,
                'level' => 'very_good',
                'confidence' => 'عالية',
                'recommendations' => [
                    'الطالب مستعد للانتقال مع تحفظ بسيط',
                    'مراجعة نهائية قبل الانتقال',
                    'الانتقال خلال أسبوع مُوصى به'
                ]
            ];
        } elseif ($total >= 65) {
            return [
                'ready' => true,
                'level' => 'good',
                'confidence' => 'متوسطة عالية',
                'recommendations' => [
                    'الطالب مستعد للانتقال مع بعض التحسينات',
                    'تعزيز النقاط الضعيفة قبل الانتقال',
                    'الانتقال خلال أسبوعين'
                ]
            ];
        } elseif ($total >= 50) {
            return [
                'ready' => false,
                'level' => 'needs_improvement',
                'confidence' => 'متوسطة',
                'recommendations' => [
                    'الطالب يحتاج تحسينات قبل الانتقال',
                    'التركيز على المجالات الضعيفة',
                    'إعادة التقييم خلال شهر'
                ]
            ];
        } else {
            return [
                'ready' => false,
                'level' => 'not_ready',
                'confidence' => 'منخفضة',
                'recommendations' => [
                    'الطالب غير مستعد للانتقال حالياً',
                    'يحتاج دعم مكثف وخطة تحسين شاملة',
                    'النظر في تغيير المنهج الحالي'
                ]
            ];
        }
    }

    /**
     * الحصول على الخطوات التالية المُوصاة
     */
    protected function getNextSteps(array $readinessLevel, Student $student): array
    {
        $nextSteps = [];
        
        if ($readinessLevel['ready']) {
            $nextSteps[] = [
                'action' => 'prepare_progression',
                'title' => 'تحضير للانتقال',
                'description' => 'إعداد خطة الانتقال للمنهج التالي',
                'priority' => 'high',
                'timeline' => 'فوري'
            ];
            
            $nextSteps[] = [
                'action' => 'select_next_curriculum',
                'title' => 'اختيار المنهج التالي',
                'description' => 'تحديد المنهج الأنسب للمستوى التالي',
                'priority' => 'high',
                'timeline' => 'خلال 3 أيام'
            ];
            
            $nextSteps[] = [
                'action' => 'create_transition_plan',
                'title' => 'إنشاء خطة الانتقال',
                'description' => 'وضع جدول زمني للانتقال التدريجي',
                'priority' => 'medium',
                'timeline' => 'خلال أسبوع'
            ];
        } else {
            $nextSteps[] = [
                'action' => 'identify_improvement_areas',
                'title' => 'تحديد مجالات التحسين',
                'description' => 'تحليل النقاط التي تحتاج تطوير',
                'priority' => 'high',
                'timeline' => 'فوري'
            ];
            
            $nextSteps[] = [
                'action' => 'create_improvement_plan',
                'title' => 'إنشاء خطة التحسين',
                'description' => 'وضع خطة عمل لتطوير الأداء',
                'priority' => 'high',
                'timeline' => 'خلال أسبوع'
            ];
            
            $nextSteps[] = [
                'action' => 'schedule_follow_up',
                'title' => 'جدولة المتابعة',
                'description' => 'تحديد مواعيد إعادة التقييم',
                'priority' => 'medium',
                'timeline' => 'خلال شهر'
            ];
        }
        
        return $nextSteps;
    }

    /**
     * تنفيذ عملية الانتقال للمنهج التالي
     */
    public function executeProgression(Student $student, int $newCurriculumId, array $transitionPlan = []): array
    {
        try {
            $oldProgress = $student->progress;
            $newCurriculum = Curriculum::findOrFail($newCurriculumId);
            
            // حفظ بيانات الانتقال
            $transitionData = [
                'from_curriculum_id' => $oldProgress->curriculum_id,
                'to_curriculum_id' => $newCurriculumId,
                'transition_date' => now(),
                'readiness_score' => $transitionPlan['readiness_score'] ?? null,
                'transition_plan' => $transitionPlan,
                'executed_by' => auth()->id() ?? null
            ];
            
            // تحديث بيانات التقدم
            $oldProgress->update([
                'curriculum_id' => $newCurriculumId,
                'current_page' => 1,
                'verses_memorized' => 0,
                'completion_percentage' => 0,
                'previous_curriculum_data' => json_encode([
                    'curriculum_id' => $oldProgress->curriculum_id,
                    'current_page' => $oldProgress->current_page,
                    'verses_memorized' => $oldProgress->verses_memorized,
                    'completion_percentage' => $oldProgress->completion_percentage,
                    'transitioned_at' => now()
                ]),
                'curriculum_changed_at' => now(),
                'transition_data' => json_encode($transitionData)
            ]);
            
            // إعادة تعيين التتبع اليومي
            $this->dailyTrackingService->resetDailyTracking($student);
            
            // إنشاء سجل الانتقال
            $this->createProgressionRecord($student, $transitionData);
            
            Log::info("تم تنفيذ انتقال الطالب بنجاح", [
                'student_id' => $student->id,
                'from_curriculum' => $transitionData['from_curriculum_id'],
                'to_curriculum' => $newCurriculumId
            ]);
            
            return [
                'success' => true,
                'message' => 'تم تنفيذ الانتقال بنجاح',
                'transition_data' => $transitionData
            ];
            
        } catch (\Exception $e) {
            Log::error("خطأ في تنفيذ انتقال الطالب", [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'فشل في تنفيذ الانتقال: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على معايير التتبع اليومي
     */
    protected function getDailyTrackingCriteria(Student $student): array
    {
        $progress = $student->progress;
        
        if (!$progress) {
            return [
                'consistency_score' => 0,
                'daily_completion_rate' => 0,
                'last_activity_days' => 999
            ];
        }
        
        $lastUpdate = $progress->daily_tracking_updated_at 
            ? Carbon::parse($progress->daily_tracking_updated_at)
            : Carbon::now()->subDays(30);
            
        $daysSinceLastUpdate = $lastUpdate->diffInDays(Carbon::now());
        
        // حساب نقاط الانتظام
        $consistencyScore = 100;
        if ($daysSinceLastUpdate > 1) $consistencyScore -= min($daysSinceLastUpdate * 10, 80);
        
        $dailyCompletionRate = $progress->today_completion_percentage ?? 0;
        
        return [
            'consistency_score' => max($consistencyScore, 0),
            'daily_completion_rate' => $dailyCompletionRate,
            'last_activity_days' => $daysSinceLastUpdate,
            'today_status' => $progress->today_status ?? 'not_started'
        ];
    }

    /**
     * الحصول على معايير الجودة والإتقان
     */
    protected function getQualityCriteria(Collection $sessions): array
    {
        if ($sessions->isEmpty()) {
            return [
                'error_rate' => 10,
                'mastery_level' => 0,
                'retention_rate' => 0
            ];
        }
        
        $totalErrors = $sessions->sum(function($session) {
            return $session->recitationErrors->count();
        });
        
        $errorRate = $sessions->count() > 0 ? $totalErrors / $sessions->count() : 10;
        $masteryLevel = $sessions->where('status', 'completed')->avg('score') ?? 0;
        
        // حساب معدل الاحتفاظ (بناءً على الأداء المتسق)
        $retentionRate = $this->calculateRetentionRate($sessions);
        
        return [
            'error_rate' => round($errorRate, 2),
            'mastery_level' => round($masteryLevel, 2),
            'retention_rate' => $retentionRate
        ];
    }

    /**
     * حسابات مساعدة للمعايير
     */
    protected function getDaysInCurrentCurriculum(StudentProgress $progress): int
    {
        $startDate = $progress->curriculum_changed_at 
            ? Carbon::parse($progress->curriculum_changed_at)
            : Carbon::parse($progress->created_at);
            
        return $startDate->diffInDays(Carbon::now());
    }

    protected function calculateSessionConsistency(Collection $sessions): float
    {
        if ($sessions->isEmpty()) return 0;
        
        $totalDays = 14;
        $activeDays = $sessions->groupBy(function($session) {
            return $session->created_at->format('Y-m-d');
        })->count();
        
        return ($activeDays / $totalDays) * 100;
    }

    protected function calculateImprovementTrend(Collection $sessions): string
    {
        if ($sessions->count() < 4) return 'insufficient_data';
        
        $recentScores = $sessions->take(5)->pluck('score')->filter();
        $olderScores = $sessions->skip(5)->take(5)->pluck('score')->filter();
        
        if ($recentScores->isEmpty() || $olderScores->isEmpty()) {
            return 'insufficient_data';
        }
        
        $recentAvg = $recentScores->average();
        $olderAvg = $olderScores->average();
        $difference = $recentAvg - $olderAvg;
        
        if ($difference > 5) return 'improving';
        if ($difference < -5) return 'declining';
        return 'stable';
    }

    protected function calculateRetentionRate(Collection $sessions): float
    {
        // حساب معدل الاحتفاظ بناءً على تحسن الأداء مع الوقت
        if ($sessions->count() < 3) return 50;
        
        $chronologicalSessions = $sessions->sortBy('created_at');
        $improvementCount = 0;
        $totalComparisons = 0;
        
        for ($i = 1; $i < $chronologicalSessions->count(); $i++) {
            $current = $chronologicalSessions->values()[$i];
            $previous = $chronologicalSessions->values()[$i-1];
            
            if ($current->score && $previous->score) {
                $totalComparisons++;
                if ($current->score >= $previous->score * 0.9) { // الاحتفاظ بـ 90% على الأقل
                    $improvementCount++;
                }
            }
        }
        
        return $totalComparisons > 0 ? ($improvementCount / $totalComparisons) * 100 : 50;
    }

    /**
     * إنشاء سجل الانتقال
     */
    protected function createProgressionRecord(Student $student, array $transitionData): void
    {
        // يمكن إنشاء جدول منفصل لسجلات الانتقال إذا لزم الأمر
        Log::info("سجل انتقال جديد", [
            'student_id' => $student->id,
            'transition_data' => $transitionData
        ]);
    }
}
