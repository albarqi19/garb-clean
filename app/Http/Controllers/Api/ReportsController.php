<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\QuranCircle;
use App\Models\RecitationSession;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\Mosque;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * API الإحصائيات والتقارير العامة
 */
class ReportsController extends Controller
{
    /**
     * إحصائيات عامة شاملة للنظام
     */
    public function generalStats(): JsonResponse
    {
        try {
            // إحصائيات المعلمين
            $teacherStats = [
                'إجمالي_المعلمين' => Teacher::count(),
                'المعلمين_النشطين' => Teacher::where('is_active', true)->count(),
                'المعلمين_غير_النشطين' => Teacher::where('is_active', false)->count(),
                'متوسط_عدد_الحلقات_لكل_معلم' => round(QuranCircle::count() / max(Teacher::count(), 1), 2),
            ];

            // إحصائيات الطلاب
            $studentStats = [
                'إجمالي_الطلاب' => Student::count(),
                'الطلاب_النشطين' => Student::where('is_active', true)->count(),
                'الطلاب_غير_النشطين' => Student::where('is_active', false)->count(),
                'متوسط_العمر' => round(Student::whereNotNull('birth_date')->get()->avg(function ($student) {
                    return $student->birth_date->age;
                }), 1),
            ];

            // إحصائيات الحلقات
            $circleStats = [
                'إجمالي_الحلقات' => QuranCircle::count(),
                'الحلقات_النشطة' => QuranCircle::where('is_active', true)->count(),
                'الحلقات_غير_النشطة' => QuranCircle::where('is_active', false)->count(),
                'متوسط_عدد_الطلاب_لكل_حلقة' => round(Student::count() / max(QuranCircle::count(), 1), 2),
            ];

            // إحصائيات المساجد
            $mosqueStats = [
                'إجمالي_المساجد' => Mosque::count(),
                'متوسط_المعلمين_لكل_مسجد' => round(Teacher::count() / max(Mosque::count(), 1), 2),
                'متوسط_الطلاب_لكل_مسجد' => round(Student::count() / max(Mosque::count(), 1), 2),
            ];

            // إحصائيات التسميع
            $recitationStats = RecitationSession::selectRaw('
                COUNT(*) as total_sessions,
                AVG(quality_score) as avg_quality,
                SUM(pages_recited) as total_pages,
                COUNT(CASE WHEN quality_score >= 90 THEN 1 END) as excellent_sessions,
                COUNT(CASE WHEN quality_score >= 70 AND quality_score < 90 THEN 1 END) as good_sessions,
                COUNT(CASE WHEN quality_score < 70 THEN 1 END) as needs_improvement_sessions
            ')->first();

            // إحصائيات الحضور للشهر الحالي
            $currentMonth = now();
            $attendanceStats = [
                'معدل_حضور_الطلاب_الشهر_الحالي' => $this->calculateStudentAttendanceRate($currentMonth),
                'معدل_حضور_المعلمين_الشهر_الحالي' => $this->calculateTeacherAttendanceRate($currentMonth),
                'إجمالي_أيام_حضور_الطلاب' => StudentAttendance::where('is_present', true)->count(),
                'إجمالي_أيام_حضور_المعلمين' => TeacherAttendance::where('is_present', true)->count(),
            ];

            // أفضل الإحصائيات
            $topStats = [
                'أفضل_طالب_في_الحفظ' => $this->getTopStudentByMemorization(),
                'أفضل_طالب_في_جودة_التسميع' => $this->getTopStudentByRecitationQuality(),
                'أفضل_معلم_في_عدد_الطلاب' => $this->getTopTeacherByStudentCount(),
                'أفضل_حلقة_في_الأداء' => $this->getTopPerformingCircle(),
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب الإحصائيات العامة بنجاح',
                'الإحصائيات_العامة' => [
                    'المعلمين' => $teacherStats,
                    'الطلاب' => $studentStats,
                    'الحلقات' => $circleStats,
                    'المساجد' => $mosqueStats,
                    'التسميع' => [
                        'إجمالي_الجلسات' => $recitationStats->total_sessions ?? 0,
                        'متوسط_الجودة' => round($recitationStats->avg_quality ?? 0, 2),
                        'إجمالي_الصفحات_المسمعة' => $recitationStats->total_pages ?? 0,
                        'الجلسات_الممتازة' => $recitationStats->excellent_sessions ?? 0,
                        'الجلسات_الجيدة' => $recitationStats->good_sessions ?? 0,
                        'الجلسات_تحتاج_تحسين' => $recitationStats->needs_improvement_sessions ?? 0,
                        'نسبة_النجاح' => $recitationStats->total_sessions > 0 
                            ? round((($recitationStats->excellent_sessions + $recitationStats->good_sessions) / $recitationStats->total_sessions) * 100, 2)
                            : 0
                    ],
                    'الحضور' => $attendanceStats,
                    'أفضل_الأداءات' => $topStats
                ],
                'تاريخ_التقرير' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب الإحصائيات العامة',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير الأداء الشهري
     */
    public function monthlyPerformanceReport(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // إحصائيات التسميع للشهر
            $monthlyRecitation = RecitationSession::whereBetween('session_date', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_sessions,
                    AVG(quality_score) as avg_quality,
                    SUM(pages_recited) as total_pages,
                    COUNT(DISTINCT student_id) as active_students
                ')->first();

            // إحصائيات الحضور للشهر
            $monthlyAttendance = [
                'الطلاب' => StudentAttendance::whereBetween('date', [$startDate, $endDate])
                    ->selectRaw('
                        COUNT(*) as total_days,
                        SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days,
                        COUNT(DISTINCT student_id) as active_students
                    ')->first(),
                'المعلمين' => TeacherAttendance::whereBetween('date', [$startDate, $endDate])
                    ->selectRaw('
                        COUNT(*) as total_days,
                        SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days,
                        COUNT(DISTINCT teacher_id) as active_teachers
                    ')->first()
            ];

            // أفضل الطلاب في الشهر
            $topStudents = RecitationSession::whereBetween('session_date', [$startDate, $endDate])
                ->with('student:id,name,student_number')
                ->selectRaw('
                    student_id,
                    COUNT(*) as sessions_count,
                    AVG(quality_score) as avg_quality,
                    SUM(pages_recited) as total_pages
                ')
                ->groupBy('student_id')
                ->orderByDesc('avg_quality')
                ->limit(10)
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->student->id,
                        'الاسم' => $record->student->name,
                        'رقم_الطالب' => $record->student->student_number,
                        'عدد_الجلسات' => $record->sessions_count,
                        'متوسط_الجودة' => round($record->avg_quality, 2),
                        'إجمالي_الصفحات' => $record->total_pages
                    ];
                });

            // أفضل المعلمين في الشهر
            $topTeachers = RecitationSession::whereBetween('session_date', [$startDate, $endDate])
                ->with('teacher.user:id,name')
                ->selectRaw('
                    teacher_id,
                    COUNT(*) as sessions_count,
                    AVG(quality_score) as avg_quality,
                    COUNT(DISTINCT student_id) as students_count
                ')
                ->groupBy('teacher_id')
                ->orderByDesc('avg_quality')
                ->limit(10)
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->teacher->id,
                        'الاسم' => $record->teacher->user->name ?? 'غير محدد',
                        'عدد_الجلسات' => $record->sessions_count,
                        'متوسط_الجودة' => round($record->avg_quality, 2),
                        'عدد_الطلاب_النشطين' => $record->students_count
                    ];
                });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب تقرير الأداء الشهري بنجاح',
                'الشهر' => $startDate->format('Y-m'),
                'اسم_الشهر' => $startDate->translatedFormat('F Y'),
                'تقرير_الأداء' => [
                    'التسميع' => [
                        'إجمالي_الجلسات' => $monthlyRecitation->total_sessions ?? 0,
                        'متوسط_الجودة' => round($monthlyRecitation->avg_quality ?? 0, 2),
                        'إجمالي_الصفحات' => $monthlyRecitation->total_pages ?? 0,
                        'الطلاب_النشطين' => $monthlyRecitation->active_students ?? 0
                    ],
                    'الحضور' => [
                        'الطلاب' => [
                            'إجمالي_الأيام' => $monthlyAttendance['الطلاب']->total_days ?? 0,
                            'أيام_الحضور' => $monthlyAttendance['الطلاب']->present_days ?? 0,
                            'نسبة_الحضور' => $monthlyAttendance['الطلاب']->total_days > 0 
                                ? round(($monthlyAttendance['الطلاب']->present_days / $monthlyAttendance['الطلاب']->total_days) * 100, 2)
                                : 0,
                            'الطلاب_النشطين' => $monthlyAttendance['الطلاب']->active_students ?? 0
                        ],
                        'المعلمين' => [
                            'إجمالي_الأيام' => $monthlyAttendance['المعلمين']->total_days ?? 0,
                            'أيام_الحضور' => $monthlyAttendance['المعلمين']->present_days ?? 0,
                            'نسبة_الحضور' => $monthlyAttendance['المعلمين']->total_days > 0 
                                ? round(($monthlyAttendance['المعلمين']->present_days / $monthlyAttendance['المعلمين']->total_days) * 100, 2)
                                : 0,
                            'المعلمين_النشطين' => $monthlyAttendance['المعلمين']->active_teachers ?? 0
                        ]
                    ],
                    'أفضل_الطلاب' => $topStudents,
                    'أفضل_المعلمين' => $topTeachers
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب تقرير الأداء الشهري',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير إحصائيات المساجد
     */
    public function mosqueStats(): JsonResponse
    {
        try {
            $mosques = Mosque::with(['teachers', 'students', 'circles'])
                ->get()
                ->map(function ($mosque) {
                    $studentIds = $mosque->students->pluck('id');
                    
                    // إحصائيات التسميع لطلاب المسجد
                    $recitationStats = RecitationSession::whereIn('student_id', $studentIds)
                        ->selectRaw('
                            COUNT(*) as total_sessions,
                            AVG(quality_score) as avg_quality,
                            SUM(pages_recited) as total_pages
                        ')->first();

                    // إحصائيات الحضور
                    $attendanceRate = $studentIds->count() > 0 ? $mosque->students->avg(function ($student) {
                        $totalDays = $student->attendances()->count();
                        $presentDays = $student->attendances()->where('is_present', true)->count();
                        return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
                    }) : 0;

                    return [
                        'id' => $mosque->id,
                        'اسم_المسجد' => $mosque->name,
                        'العنوان' => $mosque->address ?? 'غير محدد',
                        'عدد_المعلمين' => $mosque->teachers->count(),
                        'عدد_الطلاب' => $mosque->students->count(),
                        'عدد_الحلقات' => $mosque->circles->count(),
                        'إحصائيات_التسميع' => [
                            'إجمالي_الجلسات' => $recitationStats->total_sessions ?? 0,
                            'متوسط_الجودة' => round($recitationStats->avg_quality ?? 0, 2),
                            'إجمالي_الصفحات' => $recitationStats->total_pages ?? 0
                        ],
                        'نسبة_الحضور' => round($attendanceRate, 2),
                        'متوسط_الطلاب_لكل_معلم' => $mosque->teachers->count() > 0 
                            ? round($mosque->students->count() / $mosque->teachers->count(), 2)
                            : 0
                    ];
                })
                ->sortByDesc('عدد_الطلاب')
                ->values();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب إحصائيات المساجد بنجاح',
                'إجمالي_المساجد' => $mosques->count(),
                'البيانات' => $mosques
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب إحصائيات المساجد',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حساب معدل حضور الطلاب لشهر محدد
     */
    private function calculateStudentAttendanceRate($month)
    {
        $attendances = StudentAttendance::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days
            ')->first();

        return $attendances->total_days > 0 
            ? round(($attendances->present_days / $attendances->total_days) * 100, 2)
            : 0;
    }

    /**
     * حساب معدل حضور المعلمين لشهر محدد
     */
    private function calculateTeacherAttendanceRate($month)
    {
        $attendances = TeacherAttendance::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days
            ')->first();

        return $attendances->total_days > 0 
            ? round(($attendances->present_days / $attendances->total_days) * 100, 2)
            : 0;
    }

    /**
     * أفضل طالب في الحفظ
     */
    private function getTopStudentByMemorization()
    {
        $student = Student::with('curriculum:id,student_id,memorized_pages')
            ->get()
            ->sortByDesc(function ($student) {
                return $student->curriculum->memorized_pages ?? 0;
            })
            ->first();

        return $student ? [
            'id' => $student->id,
            'الاسم' => $student->name,
            'رقم_الطالب' => $student->student_number,
            'الصفحات_المحفوظة' => $student->curriculum->memorized_pages ?? 0
        ] : null;
    }

    /**
     * أفضل طالب في جودة التسميع
     */
    private function getTopStudentByRecitationQuality()
    {
        $result = RecitationSession::with('student:id,name,student_number')
            ->selectRaw('student_id, AVG(quality_score) as avg_quality')
            ->groupBy('student_id')
            ->having('avg_quality', '>', 0)
            ->orderByDesc('avg_quality')
            ->first();

        return $result ? [
            'id' => $result->student->id,
            'الاسم' => $result->student->name,
            'رقم_الطالب' => $result->student->student_number,
            'متوسط_الجودة' => round($result->avg_quality, 2)
        ] : null;
    }

    /**
     * أفضل معلم في عدد الطلاب
     */
    private function getTopTeacherByStudentCount()
    {
        $teacher = Teacher::with(['user:id,name', 'circles.students'])
            ->get()
            ->sortByDesc(function ($teacher) {
                return $teacher->circles->sum(function ($circle) {
                    return $circle->students->count();
                });
            })
            ->first();

        return $teacher ? [
            'id' => $teacher->id,
            'الاسم' => $teacher->user->name ?? 'غير محدد',
            'عدد_الطلاب' => $teacher->circles->sum(function ($circle) {
                return $circle->students->count();
            })
        ] : null;
    }

    /**
     * أفضل حلقة في الأداء
     */
    private function getTopPerformingCircle()
    {
        $circle = QuranCircle::with(['teacher.user:id,name', 'students'])
            ->where('is_active', true)
            ->get()
            ->map(function ($circle) {
                $studentIds = $circle->students->pluck('id');
                $avgQuality = RecitationSession::whereIn('student_id', $studentIds)
                    ->avg('quality_score') ?? 0;
                
                $circle->performance_score = $avgQuality;
                return $circle;
            })
            ->sortByDesc('performance_score')
            ->first();

        return $circle ? [
            'id' => $circle->id,
            'اسم_الحلقة' => $circle->name,
            'المعلم' => $circle->teacher->user->name ?? 'غير محدد',
            'عدد_الطلاب' => $circle->students->count(),
            'متوسط_الجودة' => round($circle->performance_score, 2)
        ] : null;
    }
}
