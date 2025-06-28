<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Curriculum;
use App\Models\StudentProgress;
use App\Models\RecitationSession;
use App\Models\RecitationError;
use App\Models\CurriculumAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FlexibleCurriculumService
{
    protected $dailyTrackingService;

    public function __construct(DailyCurriculumTrackingService $dailyTrackingService)
    {
        $this->dailyTrackingService = $dailyTrackingService;
    }
    /**
     * تحليل أداء الطالب وإنشاء اقتراحات مرنة للانتقال
     */
    public function analyzeStudentPerformance(Student $student)
    {
        $progress = $student->studentProgress;
        if (!$progress) {
            return [
                'status' => 'no_progress',
                'message' => 'لا توجد بيانات تقدم للطالب',
                'suggestions' => []
            ];
        }

        // جمع بيانات الأداء
        $performanceData = $this->collectPerformanceData($student, $progress);
        
        // تحليل الأداء وإنشاء الاقتراحات
        $analysis = $this->performAnalysis($performanceData);
        
        // إنشاء تنبيهات مرنة
        $alerts = $this->generateFlexibleAlerts($analysis, $student);
        
        return [
            'status' => 'analyzed',
            'performance_data' => $performanceData,
            'analysis' => $analysis,
            'alerts' => $alerts,
            'suggestions' => $this->generateSuggestions($analysis, $student)
        ];
    }

    /**
     * جمع بيانات الأداء الشاملة للطالب
     */
    private function collectPerformanceData(Student $student, StudentProgress $progress)
    {
        $currentDate = Carbon::now();
        $twoWeeksAgo = $currentDate->copy()->subWeeks(2);
        
        // جلسات التسميع الأخيرة
        $recentSessions = RecitationSession::where('student_id', $student->id)
            ->where('created_at', '>=', $twoWeeksAgo)
            ->with('recitationErrors')
            ->orderBy('created_at', 'desc')
            ->get();

        // حساب الإحصائيات
        $totalSessions = $recentSessions->count();
        $completedSessions = $recentSessions->where('status', 'completed')->count();
        $averageScore = $recentSessions->avg('score') ?? 0;
        $totalErrors = $recentSessions->sum(function($session) {
            return $session->recitationErrors->count();
        });

        // تحليل أنواع الأخطاء
        $errorTypes = [];
        foreach ($recentSessions as $session) {
            foreach ($session->recitationErrors as $error) {
                $errorTypes[$error->error_type] = ($errorTypes[$error->error_type] ?? 0) + 1;
            }
        }

        // حساب معدل التقدم اليومي
        $dailyProgress = $this->calculateDailyProgressRate($student);
        
        // فحص الانتظام في التسميع
        $consistencyRate = $this->calculateConsistencyRate($recentSessions);

        return [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'completion_rate' => $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0,
            'average_score' => round($averageScore, 2),
            'total_errors' => $totalErrors,
            'error_rate' => $totalSessions > 0 ? $totalErrors / $totalSessions : 0,
            'error_types' => $errorTypes,
            'daily_progress_rate' => $dailyProgress,
            'consistency_rate' => $consistencyRate,
            'current_curriculum_progress' => $progress->progress_percentage,
            'days_in_current_curriculum' => $this->getDaysInCurrentCurriculum($progress),
            'recent_sessions_data' => $recentSessions->map(function($session) {
                return [
                    'date' => $session->created_at->format('Y-m-d'),
                    'score' => $session->score,
                    'status' => $session->status,
                    'errors_count' => $session->recitationErrors->count()
                ];
            })
        ];
    }

    /**
     * تحليل بيانات الأداء وإنشاء التقييم
     */
    private function performAnalysis($performanceData)
    {
        $analysis = [
            'overall_performance' => 'متوسط',
            'readiness_for_next_level' => false,
            'areas_of_strength' => [],
            'areas_for_improvement' => [],
            'performance_trend' => 'مستقر',
            'recommendation_confidence' => 0
        ];

        // تحليل الأداء العام
        if ($performanceData['average_score'] >= 85 && $performanceData['completion_rate'] >= 80) {
            $analysis['overall_performance'] = 'ممتاز';
        } elseif ($performanceData['average_score'] >= 70 && $performanceData['completion_rate'] >= 60) {
            $analysis['overall_performance'] = 'جيد';
        } elseif ($performanceData['average_score'] < 50 || $performanceData['completion_rate'] < 40) {
            $analysis['overall_performance'] = 'يحتاج تحسين';
        }

        // تحديد نقاط القوة
        if ($performanceData['consistency_rate'] >= 80) {
            $analysis['areas_of_strength'][] = 'انتظام في التسميع';
        }
        if ($performanceData['error_rate'] <= 2) {
            $analysis['areas_of_strength'][] = 'قلة الأخطاء';
        }
        if ($performanceData['average_score'] >= 80) {
            $analysis['areas_of_strength'][] = 'درجات عالية';
        }

        // تحديد نقاط التحسين
        if ($performanceData['consistency_rate'] < 60) {
            $analysis['areas_for_improvement'][] = 'تحسين الانتظام';
        }
        if ($performanceData['error_rate'] > 5) {
            $analysis['areas_for_improvement'][] = 'تقليل الأخطاء';
        }
        if ($performanceData['completion_rate'] < 70) {
            $analysis['areas_for_improvement'][] = 'زيادة معدل إكمال الجلسات';
        }

        // تحديد الاستعداد للمستوى التالي
        $readinessScore = 0;
        if ($performanceData['average_score'] >= 75) $readinessScore += 30;
        if ($performanceData['completion_rate'] >= 70) $readinessScore += 25;
        if ($performanceData['consistency_rate'] >= 70) $readinessScore += 20;
        if ($performanceData['current_curriculum_progress'] >= 80) $readinessScore += 25;

        $analysis['readiness_for_next_level'] = $readinessScore >= 70;
        $analysis['recommendation_confidence'] = $readinessScore;

        // تحليل الاتجاه (يحتاج بيانات تاريخية أكثر)
        if (count($performanceData['recent_sessions_data']) >= 5) {
            $recentScores = collect($performanceData['recent_sessions_data'])->pluck('score')->toArray();
            $trend = $this->calculateTrend($recentScores);
            $analysis['performance_trend'] = $trend;
        }

        return $analysis;
    }

    /**
     * إنشاء تنبيهات مرنة بناءً على التحليل
     */
    private function generateFlexibleAlerts($analysis, Student $student)
    {
        $alerts = [];
        $currentDate = Carbon::now();

        // تنبيه الاستعداد للمستوى التالي
        if ($analysis['readiness_for_next_level']) {
            $confidence = $analysis['recommendation_confidence'];
            $urgency = $confidence >= 85 ? 'عالية' : ($confidence >= 70 ? 'متوسطة' : 'منخفضة');
            
            $alerts[] = [
                'type' => 'level_progression_suggestion',
                'priority' => $urgency,
                'title' => 'اقتراح الانتقال للمستوى التالي',
                'message' => "الطالب {$student->name} يظهر استعداداً للانتقال للمستوى التالي بنسبة ثقة {$confidence}%",
                'action_required' => false,
                'suggested_actions' => [
                    'مراجعة أداء الطالب مع المعلم',
                    'إجراء تقييم شامل قبل الانتقال',
                    'تحديد المنهج المناسب للمستوى التالي'
                ],
                'created_at' => $currentDate,
                'expires_at' => $currentDate->copy()->addDays(7)
            ];
        }

        // تنبيه التحسينات المطلوبة
        if (!empty($analysis['areas_for_improvement'])) {
            $alerts[] = [
                'type' => 'improvement_needed',
                'priority' => 'متوسطة',
                'title' => 'مجالات تحتاج تحسين',
                'message' => "يحتاج الطالب {$student->name} للتركيز على: " . implode(', ', $analysis['areas_for_improvement']),
                'action_required' => true,
                'suggested_actions' => $this->getSuggestedActionsForImprovement($analysis['areas_for_improvement']),
                'created_at' => $currentDate,
                'expires_at' => $currentDate->copy()->addDays(14)
            ];
        }

        // تنبيه الأداء المتميز
        if ($analysis['overall_performance'] === 'ممتاز') {
            $alerts[] = [
                'type' => 'excellent_performance',
                'priority' => 'منخفضة',
                'title' => 'أداء متميز',
                'message' => "الطالب {$student->name} يحقق أداءً متميزاً",
                'action_required' => false,
                'suggested_actions' => ['تشجيع الطالب', 'النظر في تحديات إضافية'],
                'created_at' => $currentDate,
                'expires_at' => $currentDate->copy()->addDays(30)
            ];
        }

        return $alerts;
    }

    /**
     * إنشاء اقتراحات مخصصة للطالب
     */
    private function generateSuggestions($analysis, Student $student)
    {
        $suggestions = [];

        if ($analysis['readiness_for_next_level']) {
            $nextCurriculum = $this->suggestNextCurriculum($student);
            if ($nextCurriculum) {
                $suggestions[] = [
                    'type' => 'curriculum_progression',
                    'title' => 'انتقال للمنهج التالي',
                    'description' => "يُقترح انتقال الطالب لمنهج: {$nextCurriculum->name}",
                    'curriculum_id' => $nextCurriculum->id,
                    'confidence' => $analysis['recommendation_confidence']
                ];
            }
        }

        // اقتراحات التحسين
        foreach ($analysis['areas_for_improvement'] as $area) {
            $suggestions[] = [
                'type' => 'improvement_plan',
                'title' => "خطة تحسين: {$area}",
                'description' => $this->getImprovementDescription($area),
                'action_items' => $this->getActionItemsForImprovement($area)
            ];
        }

        return $suggestions;
    }

    /**
     * اقتراح المنهج التالي للطالب
     */
    private function suggestNextCurriculum(Student $student)
    {
        $currentProgress = $student->studentProgress;
        if (!$currentProgress) return null;

        $currentCurriculum = $currentProgress->curriculum;
        if (!$currentCurriculum) return null;

        // البحث عن المنهج التالي بناءً على المستوى
        $nextCurriculum = Curriculum::where('level', $currentCurriculum->level + 1)
            ->where('is_active', true)
            ->first();

        if (!$nextCurriculum) {
            // البحث عن منهج في نفس المستوى ولكن أكثر تقدماً
            $nextCurriculum = Curriculum::where('level', $currentCurriculum->level)
                ->where('difficulty_order', '>', $currentCurriculum->difficulty_order ?? 0)
                ->where('is_active', true)
                ->orderBy('difficulty_order')
                ->first();
        }

        return $nextCurriculum;
    }

    /**
     * حساب معدل التقدم اليومي
     */
    private function calculateDailyProgressRate(Student $student)
    {
        $progress = $student->studentProgress;
        if (!$progress) return 0;

        $daysInCurriculum = $this->getDaysInCurrentCurriculum($progress);
        if ($daysInCurriculum <= 0) return 0;

        return $progress->progress_percentage / $daysInCurriculum;
    }

    /**
     * حساب معدل الانتظام
     */
    private function calculateConsistencyRate($sessions)
    {
        if ($sessions->isEmpty()) return 0;

        $totalDays = 14; // آخر أسبوعين
        $activeDays = $sessions->groupBy(function($session) {
            return $session->created_at->format('Y-m-d');
        })->count();

        return ($activeDays / $totalDays) * 100;
    }

    /**
     * حساب عدد الأيام في المنهج الحالي
     */
    private function getDaysInCurrentCurriculum(StudentProgress $progress)
    {
        if (!$progress->created_at) return 0;
        
        return Carbon::now()->diffInDays($progress->created_at);
    }

    /**
     * حساب اتجاه الأداء
     */
    private function calculateTrend($scores)
    {
        if (count($scores) < 3) return 'مستقر';

        $firstHalf = array_slice($scores, 0, floor(count($scores) / 2));
        $secondHalf = array_slice($scores, floor(count($scores) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAvg - $firstAvg;

        if ($difference > 5) return 'متحسن';
        if ($difference < -5) return 'متراجع';
        return 'مستقر';
    }

    /**
     * الحصول على إجراءات التحسين المقترحة
     */
    private function getSuggestedActionsForImprovement($improvements)
    {
        $actions = [];
        
        foreach ($improvements as $improvement) {
            switch ($improvement) {
                case 'تحسين الانتظام':
                    $actions[] = 'وضع جدول زمني ثابت للتسميع';
                    $actions[] = 'تذكير يومي للطالب';
                    break;
                case 'تقليل الأخطاء':
                    $actions[] = 'مراجعة القواعد الأساسية';
                    $actions[] = 'تسميع أجزاء أقصر بتركيز أكبر';
                    break;
                case 'زيادة معدل إكمال الجلسات':
                    $actions[] = 'تقسيم الجلسات لفترات أقصر';
                    $actions[] = 'تحفيز الطالب بالمكافآت';
                    break;
            }
        }
        
        return array_unique($actions);
    }

    /**
     * الحصول على وصف التحسين
     */
    private function getImprovementDescription($area)
    {
        $descriptions = [
            'تحسين الانتظام' => 'يحتاج الطالب لتحسين انتظامه في حضور جلسات التسميع',
            'تقليل الأخطاء' => 'يحتاج الطالب للتركيز على تقليل الأخطاء في التلاوة',
            'زيادة معدل إكمال الجلسات' => 'يحتاج الطالب لزيادة معدل إكمال جلسات التسميع'
        ];
        
        return $descriptions[$area] ?? "يحتاج الطالب للتحسين في: {$area}";
    }

    /**
     * الحصول على عناصر العمل للتحسين
     */
    private function getActionItemsForImprovement($area)
    {
        $actionItems = [
            'تحسين الانتظام' => [
                'إنشاء جدول زمني ثابت',
                'تفعيل التذكيرات اليومية',
                'متابعة دورية مع المعلم'
            ],
            'تقليل الأخطاء' => [
                'مراجعة القواعد الأساسية',
                'التسميع البطيء والمتأني',
                'التركيز على الأخطاء الشائعة'
            ],
            'زيادة معدل إكمال الجلسات' => [
                'تقسيم الجلسات لفترات أقصر',
                'وضع أهداف صغيرة قابلة للتحقيق',
                'نظام مكافآت للتحفيز'
            ]
        ];
        
        return $actionItems[$area] ?? ["التركيز على تحسين: {$area}"];
    }    /**
     * حفظ التنبيهات في قاعدة البيانات باستخدام نموذج CurriculumAlert الجديد
     */
    public function saveAlertsToDatabase($alerts, Student $student)
    {
        foreach ($alerts as $alert) {
            // التحقق من عدم وجود تنبيه مشابه حديث
            $existingAlert = CurriculumAlert::where('student_id', $student->id)
                ->where('alert_type', $alert['type'])
                ->whereNull('teacher_decision')
                ->where('created_at', '>', Carbon::now()->subDays(7))
                ->first();

            if (!$existingAlert) {
                CurriculumAlert::create([
                    'student_id' => $student->id,
                    'current_curriculum_id' => $student->progress->curriculum_id ?? null,
                    'suggested_curriculum_id' => $this->getSuggestedCurriculumId($alert, $student),
                    'alert_type' => $alert['type'],
                    'alert_message' => $alert['message'],
                    'performance_summary' => json_encode([
                        'priority' => $alert['priority'],
                        'title' => $alert['title'],
                        'action_required' => $alert['action_required'],
                        'suggested_actions' => $alert['suggested_actions'],
                        'expires_at' => $alert['expires_at']
                    ]),
                    'created_at' => $alert['created_at'],
                    'updated_at' => $alert['created_at']
                ]);

                Log::info("تم إنشاء تنبيه منهج جديد", [
                    'student_id' => $student->id,
                    'alert_type' => $alert['type'],
                    'priority' => $alert['priority']
                ]);
            }
        }
    }

    /**
     * استخراج معرف المنهج المقترح من التنبيه
     */
    private function getSuggestedCurriculumId($alert, Student $student): ?int
    {
        if ($alert['type'] === 'level_progression_suggestion') {
            $nextCurriculum = $this->suggestNextCurriculum($student);
            return $nextCurriculum?->id;
        }
        return null;
    }

    /**
     * معالجة قرار المعلم لتنبيه المنهج
     */
    public function processTeacherDecision(CurriculumAlert $alert, string $decision, ?string $notes = null, ?int $newCurriculumId = null): bool
    {
        try {
            $alert->update([
                'teacher_decision' => $decision,
                'decision_notes' => $notes,
                'decided_at' => now()
            ]);

            if ($decision === 'approved' && $newCurriculumId) {
                return $this->applyCurriculumChange($alert->student, $newCurriculumId, $alert);
            }

            Log::info("تم معالجة قرار المعلم", [
                'alert_id' => $alert->id,
                'decision' => $decision,
                'new_curriculum_id' => $newCurriculumId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("خطأ في معالجة قرار المعلم", [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * تطبيق تغيير المنهج
     */
    protected function applyCurriculumChange(Student $student, int $newCurriculumId, CurriculumAlert $alert): bool
    {
        try {
            $newCurriculum = Curriculum::findOrFail($newCurriculumId);
            $progress = $student->progress;

            if (!$progress) {
                Log::error("لا يوجد تقدم للطالب", ['student_id' => $student->id]);
                return false;
            }

            // حفظ بيانات المنهج السابق
            $oldCurriculumData = [
                'curriculum_id' => $progress->curriculum_id,
                'current_page' => $progress->current_page,
                'verses_memorized' => $progress->verses_memorized,
                'completion_percentage' => $progress->completion_percentage,
                'changed_at' => now(),
                'alert_id' => $alert->id
            ];

            // تحديث المنهج الجديد
            $progress->update([
                'curriculum_id' => $newCurriculumId,
                'current_page' => 1,
                'verses_memorized' => 0,
                'completion_percentage' => 0,
                'previous_curriculum_data' => json_encode($oldCurriculumData),
                'curriculum_changed_at' => now()
            ]);

            // إعادة تعيين التتبع اليومي
            $this->dailyTrackingService->resetDailyTracking($student);

            Log::info("تم تغيير المنهج بنجاح", [
                'student_id' => $student->id,
                'old_curriculum_id' => $oldCurriculumData['curriculum_id'],
                'new_curriculum_id' => $newCurriculumId,
                'alert_id' => $alert->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("خطأ في تطبيق تغيير المنهج", [
                'student_id' => $student->id,
                'new_curriculum_id' => $newCurriculumId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * جلب التنبيهات المعلقة لجميع الطلاب
     */
    public function getPendingAlerts(): Collection
    {
        return CurriculumAlert::with(['student', 'currentCurriculum', 'suggestedCurriculum'])
            ->whereNull('teacher_decision')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * جلب التنبيهات المعلقة لطالب محدد
     */
    public function getPendingAlertsForStudent(Student $student): Collection
    {
        return CurriculumAlert::with(['currentCurriculum', 'suggestedCurriculum'])
            ->where('student_id', $student->id)
            ->whereNull('teacher_decision')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * تقييم جميع الطلاب النشطين وإنشاء التنبيهات اللازمة
     */
    public function evaluateAllActiveStudents(): array
    {
        $results = [
            'evaluated' => 0,
            'alerts_created' => 0,
            'errors' => []
        ];

        $activeStudents = Student::with(['progress.curriculum'])
            ->whereHas('progress')
            ->where('is_active', true)
            ->get();

        foreach ($activeStudents as $student) {
            try {
                $analysis = $this->analyzeStudentPerformance($student);
                $results['evaluated']++;

                if (!empty($analysis['alerts'])) {
                    $this->saveAlertsToDatabase($analysis['alerts'], $student);
                    $results['alerts_created'] += count($analysis['alerts']);
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ];
                Log::error("خطأ في تقييم الطالب", [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("تم تقييم جميع الطلاب النشطين", $results);
        return $results;
    }

    /**
     * تحديث التحليل ليتكامل مع التتبع اليومي
     */
    public function analyzeWithDailyTracking(Student $student): array
    {
        $baseAnalysis = $this->analyzeStudentPerformance($student);
        
        // إضافة بيانات التتبع اليومي
        $dailyTracking = $this->dailyTrackingService->getTodayRecitationForStudent($student);
        
        if ($dailyTracking) {
            $baseAnalysis['daily_tracking'] = [
                'today_content' => $dailyTracking['content'],
                'today_status' => $dailyTracking['status'],
                'completion_percentage' => $dailyTracking['completion_percentage'],
                'last_updated' => $dailyTracking['last_updated']
            ];
            
            // تحديث التحليل بناءً على الأداء اليومي
            $this->adjustAnalysisWithDailyPerformance($baseAnalysis, $dailyTracking);
        }
        
        return $baseAnalysis;
    }

    /**
     * تعديل التحليل بناءً على الأداء اليومي
     */
    private function adjustAnalysisWithDailyPerformance(array &$analysis, array $dailyTracking): void
    {
        // إذا كان الطالب لم يكمل تسميع اليوم، تقليل نقاط الاستعداد
        if ($dailyTracking['completion_percentage'] < 100) {
            $analysis['analysis']['recommendation_confidence'] -= 10;
            $analysis['analysis']['areas_for_improvement'][] = 'إكمال التسميع اليومي';
        }
        
        // إذا لم يتم التحديث لأكثر من 3 أيام، إضافة تنبيه
        $lastUpdate = Carbon::parse($dailyTracking['last_updated']);
        if ($lastUpdate->diffInDays(Carbon::now()) > 3) {
            $analysis['alerts'][] = [
                'type' => 'daily_tracking_inactive',
                'priority' => 'عالية',
                'title' => 'عدم نشاط في التتبع اليومي',
                'message' => "لم يتم تحديث التتبع اليومي للطالب منذ أكثر من 3 أيام",
                'action_required' => true,
                'suggested_actions' => [
                    'التواصل مع الطالب',
                    'تذكير بأهمية التسميع اليومي',
                    'مراجعة الجدول الزمني'
                ],
                'created_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays(7)
            ];
        }
    }
}
