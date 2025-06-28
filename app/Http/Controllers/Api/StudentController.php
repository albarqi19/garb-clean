<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\QuranCircle;
use App\Models\Teacher;
use App\Models\RecitationSession;
use App\Models\StudentAttendance;
use App\Models\StudentCurriculum;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * API الطلاب - عرض وإدارة بيانات الطلاب والمناهج والتقدم
 */
class StudentController extends Controller
{    /**
     * عرض قائمة جميع الطلاب مع معلوماتهم الأساسية
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Student::with([
                'mosque:id,name',
                'quranCircle:id,name,circle_type',
                'circleGroup:id,name',
                'curricula' => function ($q) {
                    $q->where('status', 'قيد التنفيذ')->latest();
                },
                'recitationSessions' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }
            ]);

            // فلترة حسب المسجد
            if ($request->filled('mosque_id')) {
                $query->where('mosque_id', $request->mosque_id);
            }

            // فلترة حسب الحلقة
            if ($request->filled('circle_id')) {
                $query->where('quran_circle_id', $request->circle_id);
            }

            // فلترة حسب حالة النشاط
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // البحث بالاسم أو رقم الهوية
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('identity_number', 'like', "%{$search}%");
                });
            }

            $students = $query->paginate($request->get('per_page', 15));

            $students->getCollection()->transform(function ($student) {
                $latestSession = $student->recitationSessions->first();
                $currentCurriculum = $student->curricula->first();
                
                return [
                    'id' => $student->id,
                    'الاسم' => $student->name,
                    'رقم_الهوية' => $student->identity_number,
                    'رقم_الهاتف' => $student->phone,
                    'تاريخ_الميلاد' => $student->birth_date?->format('Y-m-d'),
                    'العمر' => $student->age,
                    'المسجد' => $student->mosque->name ?? 'غير محدد',
                    'الحلقة' => $student->quranCircle->name ?? 'غير محدد',
                    'نوع_الحلقة' => $student->quranCircle->circle_type ?? 'غير محدد',
                    'المجموعة' => $student->circleGroup->name ?? 'غير محدد',
                    'نشط' => $student->is_active ? 'نعم' : 'لا',
                    'المنهج_الحالي' => $currentCurriculum ? [
                        'id' => $currentCurriculum->id,
                        'النوع' => $currentCurriculum->curriculum->type ?? 'غير محدد',
                        'الحالة' => $currentCurriculum->status,
                        'التقدم' => $currentCurriculum->progress_percentage . '%'
                    ] : null,
                    'آخر_تسميع' => $latestSession ? [
                        'التاريخ' => $latestSession->created_at->format('Y-m-d'),
                        'النوع' => $latestSession->session_type,
                        'الصفحات' => $latestSession->pages_count ?? 0,
                    ] : null,
                    'تاريخ_التسجيل' => $student->created_at->format('Y-m-d H:i'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب قائمة الطلاب بنجاح',
                'البيانات' => $students->items(),
                'معلومات_الصفحة' => [
                    'الصفحة_الحالية' => $students->currentPage(),
                    'إجمالي_الصفحات' => $students->lastPage(),
                    'إجمالي_العناصر' => $students->total(),
                    'عناصر_الصفحة' => $students->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب بيانات الطلاب',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل طالب محدد مع جميع معلوماته
     */    public function show($id): JsonResponse
    {
        try {            $student = Student::with([
                'mosque:id,name,street',
                'quranCircle:id,name,circle_type',
                'circleGroup:id,name',
                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ')->latest();
                },
                'curricula.curriculum:id,name,type',
                'recitationSessions' => function ($query) {
                    $query->orderBy('created_at', 'desc')
                          ->limit(10);
                }
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الطالب غير موجود'
                ], 404);
            }            // حساب الإحصائيات
            $totalRecitationSessions = $student->recitationSessions()->count();
            $avgQualityScore = $student->recitationSessions()->avg('grade');
            $totalVersesRecited = $student->recitationSessions()->sum('total_verses');// التقدم في الحفظ
            $currentCurriculum = $student->curricula->first();
            $progressStats = [
                'إجمالي_الصفحات_المحفوظة' => $currentCurriculum ? ($currentCurriculum->memorized_pages ?? 0) : 0,
                'نسبة_الإنجاز' => $currentCurriculum && $currentCurriculum->memorized_pages 
                    ? round(($currentCurriculum->memorized_pages / 604) * 100, 2) // 604 صفحة في المصحف
                    : 0,
                'متوسط_الصفحات_الأسبوعية' => 0 // سنحسبها لاحقاً من جلسات التسميع
            ];$data = [
                'معلومات_أساسية' => [
                    'id' => $student->id,
                    'الاسم' => $student->name,
                    'رقم_الهوية' => $student->identity_number,
                    'رقم_الهاتف' => $student->phone,
                    'تاريخ_الميلاد' => $student->birth_date?->format('Y-m-d'),
                    'العمر' => $student->age,                    'المسجد' => [
                        'id' => $student->mosque->id ?? null,
                        'الاسم' => $student->mosque->name ?? 'غير محدد',
                        'العنوان' => $student->mosque->street ?? 'غير محدد'
                    ],
                    'نشط' => $student->is_active ? 'نعم' : 'لا',
                    'ملاحظات' => $student->notes ?? 'لا توجد ملاحظات',
                    'تاريخ_التسجيل' => $student->created_at->format('Y-m-d H:i'),
                ],                'الحلقة' => [
                    'id' => $student->quranCircle->id ?? null,
                    'اسم_الحلقة' => $student->quranCircle->name ?? 'غير محدد',
                    'نوع_الحلقة' => $student->quranCircle->circle_type ?? 'غير محدد',
                    'المجموعة' => $student->circleGroup->name ?? 'غير محدد',
                    'الحالة' => $student->quranCircle->circle_status ?? 'غير محدد'
                ],
                'المنهج_الحالي' => $currentCurriculum ? [
                    'النوع' => $currentCurriculum->curriculum->type ?? 'غير محدد',
                    'الحالة' => $currentCurriculum->status,
                    'الصفحات_المحفوظة' => $currentCurriculum->memorized_pages ?? 0,
                    'نسبة_التقدم' => $currentCurriculum->progress_percentage . '%',
                    'تاريخ_آخر_تحديث' => $currentCurriculum->updated_at?->format('Y-m-d H:i'),
                ] : null,                'إحصائيات' => [
                    'التسميع' => [
                        'إجمالي_الجلسات' => $totalRecitationSessions,
                        'متوسط_الجودة' => round($avgQualityScore ?? 0, 2),
                        'إجمالي_الآيات_المسمعة' => $totalVersesRecited,
                    ],
                    'التقدم' => $progressStats
                ],'جلسات_التسميع_الأخيرة' => $student->recitationSessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'تاريخ_الجلسة' => $session->created_at->format('Y-m-d'),
                        'نوع_التسميع' => $session->recitation_type ?? 'غير محدد',
                        'من_السورة' => $session->start_surah_number ?? 0,
                        'من_الآية' => $session->start_verse ?? 0,
                        'إلى_السورة' => $session->end_surah_number ?? 0,
                        'إلى_الآية' => $session->end_verse ?? 0,
                        'درجة_الجودة' => $session->grade ?? 0,
                        'التقييم' => $session->evaluation ?? 'غير محدد',
                        'ملاحظات' => $session->teacher_notes ?? 'لا توجد ملاحظات'
                    ];
                })
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب تفاصيل الطالب بنجاح',
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب تفاصيل الطالب',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }    /**
     * عرض منهج طالب محدد مع التقدم
     */
    public function studentCurriculum($id): JsonResponse
    {
        try {
            $student = Student::with([
                'mosque:id,name',
                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ')
                          ->with(['curriculum', 'level']);
                },
                'recitationSessions' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            if ($student->curricula->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد منهج نشط للطالب'
                ], 404);
            }

            $activeCurriculum = $student->curricula->first();

            // حساب الإحصائيات التفصيلية
            $totalPages = 604; // إجمالي صفحات المصحف
            $memorizedPages = $activeCurriculum->current_page ?? 0;
            $progressPercentage = round(($memorizedPages / $totalPages) * 100, 2);

            // إحصائيات التسميع
            $totalSessions = $student->recitationSessions->count();
            $avgGrade = $student->recitationSessions->where('grade', '>', 0)->avg('grade') ?? 0;

            // تقسيم التقدم حسب الأجزاء
            $juzProgress = [];
            for ($i = 1; $i <= 30; $i++) {
                $juzPages = 20; // تقريباً 20 صفحة لكل جزء
                $juzStartPage = ($i - 1) * $juzPages + 1;
                $juzEndPage = $i * $juzPages;
                
                $memorizedInJuz = max(0, min($juzPages, $memorizedPages - $juzStartPage + 1));
                if ($memorizedPages < $juzStartPage) {
                    $memorizedInJuz = 0;
                }
                
                $juzProgress[] = [
                    'رقم_الجزء' => $i,
                    'اسم_الجزء' => "الجزء {$i}",
                    'الصفحات_المحفوظة' => max(0, $memorizedInJuz),
                    'إجمالي_الصفحات' => $juzPages,
                    'نسبة_الإنجاز' => round(($memorizedInJuz / $juzPages) * 100, 2),
                    'مكتمل' => $memorizedInJuz >= $juzPages ? 'نعم' : 'لا'
                ];
            }

            // آخر جلسات التسميع
            $recentSessions = $student->recitationSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'تاريخ_الجلسة' => $session->created_at->format('Y-m-d'),
                    'نوع_التسميع' => $session->recitation_type ?? 'غير محدد',
                    'من_السورة' => $session->start_surah_number ?? 0,
                    'من_الآية' => $session->start_verse ?? 0,
                    'إلى_السورة' => $session->end_surah_number ?? 0,
                    'إلى_الآية' => $session->end_verse ?? 0,
                    'درجة_الجودة' => $session->grade ?? 0,
                    'التقييم' => $session->evaluation ?? 'غير محدد',
                    'ملاحظات' => $session->teacher_notes ?? 'لا توجد ملاحظات'
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم جلب منهج الطالب بنجاح',
                'data' => [
                    'اسم_الطالب' => $student->name,
                    'رقم_الطالب' => $student->identity_number,
                    'المسجد' => $student->mosque->name ?? 'غير محدد',
                    'المنهج_الحالي' => [
                        'اسم_المنهج' => $activeCurriculum->curriculum->name ?? 'غير محدد',
                        'المستوى' => $activeCurriculum->level->name ?? 'غير محدد',
                        'الحالة' => $activeCurriculum->status,
                        'الصفحة_الحالية' => $memorizedPages,
                        'السورة_الحالية' => $activeCurriculum->current_surah ?? 'غير محدد',
                        'الآية_الحالية' => $activeCurriculum->current_ayah ?? 0,
                        'الصفحات_المحفوظة' => $memorizedPages,
                        'إجمالي_الصفحات' => $totalPages,
                        'نسبة_الإنجاز' => $progressPercentage,
                        'تاريخ_آخر_تحديث' => $activeCurriculum->updated_at?->format('Y-m-d H:i')
                    ],
                    'تقدم_الأجزاء' => $juzProgress,
                    'إحصائيات_التقدم' => [
                        'إجمالي_جلسات_التسميع' => $totalSessions,
                        'متوسط_درجة_الجودة' => round($avgGrade, 2),
                        'الأجزاء_المكتملة' => collect($juzProgress)->where('مكتمل', 'نعم')->count(),
                        'متوسط_الصفحات_الأسبوعية' => $totalSessions > 0 ? round($memorizedPages / max(1, $totalSessions), 2) : 0
                    ],
                    'آخر_جلسات_التسميع' => $recentSessions
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب منهج الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }    /**
     * عرض إحصائيات طالب محدد
     */
    public function studentStats($id): JsonResponse
    {
        try {
            $student = Student::with([
                'mosque:id,name',
                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ');
                },
                'recitationSessions',
                'attendances'
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            // إحصائيات التسميع
            $recitationSessions = $student->recitationSessions;
            $totalSessions = $recitationSessions->count();
            $avgGrade = $recitationSessions->where('grade', '>', 0)->avg('grade') ?? 0;
            $totalVerses = $recitationSessions->sum('total_verses') ?? 0;
            
            $excellentSessions = $recitationSessions->where('grade', '>=', 90)->count();
            $goodSessions = $recitationSessions->whereBetween('grade', [70, 89])->count();
            $needsImprovementSessions = $recitationSessions->where('grade', '<', 70)->where('grade', '>', 0)->count();

            // إحصائيات الحضور
            $attendances = $student->attendances;
            $totalAttendances = $attendances->count();
            $presentCount = $attendances->where('status', 'حاضر')->count();
            $absentCount = $attendances->where('status', 'غائب')->count();
            $lateCount = $attendances->where('status', 'متأخر')->count();
            $excusedCount = $attendances->where('status', 'معذور')->count();

            $attendancePercentage = $totalAttendances > 0 ? round(($presentCount / $totalAttendances) * 100, 2) : 0;

            // إحصائيات الحضور الشهرية
            $currentMonthAttendances = $attendances->filter(function ($attendance) {
                return $attendance->date >= now()->startOfMonth() && $attendance->date <= now()->endOfMonth();
            });
            
            $currentWeekAttendances = $attendances->filter(function ($attendance) {
                return $attendance->date >= now()->startOfWeek() && $attendance->date <= now()->endOfWeek();
            });

            // إحصائيات التقدم
            $activeCurriculum = $student->curricula->first();
            $memorizedPages = $activeCurriculum ? ($activeCurriculum->current_page ?? 0) : 0;
            $totalPages = 604;
            $progressPercentage = round(($memorizedPages / $totalPages) * 100, 2);

            // التقدم الشهري (آخر 6 أشهر)
            $monthlyProgress = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd = now()->subMonths($i)->endOfMonth();
                
                $monthSessions = $recitationSessions->filter(function ($session) use ($monthStart, $monthEnd) {
                    return $session->created_at >= $monthStart && $session->created_at <= $monthEnd;
                });

                $monthlyProgress[] = [
                    'الشهر' => $monthStart->format('Y-m'),
                    'اسم_الشهر' => $monthStart->translatedFormat('F Y'),
                    'عدد_الجلسات' => $monthSessions->count(),
                    'إجمالي_الآيات' => $monthSessions->sum('total_verses') ?? 0,
                    'متوسط_الجودة' => $monthSessions->where('grade', '>', 0)->avg('grade') ?? 0
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب إحصائيات الطالب بنجاح',
                'data' => [
                    'معلومات_الطالب' => [
                        'الاسم' => $student->name,
                        'رقم_الهوية' => $student->identity_number,
                        'المسجد' => $student->mosque->name ?? 'غير محدد'
                    ],
                    'إحصائيات_التسميع' => [
                        'إجمالي_الجلسات' => $totalSessions,
                        'متوسط_الجودة' => round($avgGrade, 2),
                        'إجمالي_الآيات_المسمعة' => $totalVerses,
                        'الجلسات_الممتازة' => $excellentSessions,
                        'الجلسات_الجيدة' => $goodSessions,
                        'الجلسات_تحتاج_تحسين' => $needsImprovementSessions,
                        'نسبة_الجلسات_الممتازة' => $totalSessions > 0 ? round(($excellentSessions / $totalSessions) * 100, 2) : 0
                    ],
                    'إحصائيات_الحضور' => [
                        'إجمالي_أيام_الحضور' => $totalAttendances,
                        'أيام_الحضور' => $presentCount,
                        'أيام_الغياب' => $absentCount,
                        'أيام_التأخير' => $lateCount,
                        'أيام_العذر' => $excusedCount,
                        'نسبة_الحضور' => $attendancePercentage,
                        'الشهر_الحالي' => $currentMonthAttendances->where('status', 'حاضر')->count(),
                        'الأسبوع_الحالي' => $currentWeekAttendances->where('status', 'حاضر')->count()
                    ],
                    'إحصائيات_التقدم' => [
                        'الصفحات_المحفوظة' => $memorizedPages,
                        'إجمالي_الصفحات' => $totalPages,
                        'نسبة_الإنجاز' => $progressPercentage,
                        'المنهج_الحالي' => $activeCurriculum ? $activeCurriculum->curriculum->name : 'لا يوجد',
                        'متوسط_الصفحات_بالجلسة' => $totalSessions > 0 ? round($memorizedPages / $totalSessions, 2) : 0
                    ],
                    'التقدم_الشهري' => $monthlyProgress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض سجل حضور طالب محدد
     */
    public function studentAttendance($id, Request $request): JsonResponse
    {
        try {
            $student = Student::find($id);

            if (!$student) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الطالب غير موجود'
                ], 404);
            }

            $query = $student->attendances();

            // فلترة بالتاريخ
            if ($request->filled('start_date')) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('date', '<=', $request->end_date);
            }

            // فلترة بالشهر
            if ($request->filled('month') && $request->filled('year')) {
                $query->whereYear('date', $request->year)
                      ->whereMonth('date', $request->month);
            }

            $attendances = $query->orderBy('date', 'desc')
                                ->paginate($request->get('per_page', 30));            $attendances->getCollection()->transform(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'التاريخ' => $attendance->date->format('Y-m-d'),
                    'اليوم' => $attendance->date->translatedFormat('l'),
                    'الفترة' => $attendance->period ?? 'غير محدد',
                    'الحالة' => $attendance->status ?? 'غير محدد',
                    'حاضر' => $attendance->status === 'حاضر' ? 'نعم' : 'لا',
                    'متأخر' => $attendance->status === 'متأخر' ? 'نعم' : 'لا',                    'وقت_الحضور' => $attendance->check_in?->format('H:i'),
                    'وقت_الانصراف' => $attendance->check_out?->format('H:i'),
                    'ملاحظات' => $attendance->notes ?? 'لا توجد ملاحظات'
                ];
            });

            // حساب إحصائيات الحضور للفترة المحددة
            $totalDays = $attendances->total();
            $presentDays = $query->whereIn('status', ['حاضر', 'متأخر'])->count();
            $lateDays = $query->where('status', 'متأخر')->count();
            $absentDays = $query->where('status', 'غائب')->count();
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب سجل حضور الطالب بنجاح',
                'اسم_الطالب' => $student->name,
                'رقم_الهوية' => $student->identity_number,                'إحصائيات_الحضور' => [
                    'إجمالي_الأيام' => $totalDays,
                    'أيام_الحضور' => $presentDays,
                    'أيام_الغياب' => $absentDays,
                    'أيام_التأخير' => $lateDays,
                    'نسبة_الحضور' => $attendanceRate . '%'
                ],
                'البيانات' => $attendances->items(),
                'معلومات_الصفحة' => [
                    'الصفحة_الحالية' => $attendances->currentPage(),
                    'إجمالي_الصفحات' => $attendances->lastPage(),
                    'إجمالي_العناصر' => $attendances->total(),
                    'عناصر_الصفحة' => $attendances->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب سجل حضور الطالب',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض جلسات تسميع طالب محدد
     */
    public function studentRecitationSessions($id, Request $request): JsonResponse
    {
        try {
            $student = Student::find($id);

            if (!$student) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الطالب غير موجود'
                ], 404);
            }            $query = $student->recitationSessions();            // فلترة بالتاريخ
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', $request->end_date);
            }

            // فلترة بدرجة الجودة
            if ($request->filled('min_quality')) {
                $query->where('grade', '>=', $request->min_quality);
            }

            // فلترة بنوع التسميع
            if ($request->filled('recitation_type')) {
                $query->where('recitation_type', $request->recitation_type);
            }

            // فلترة بالحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلترة بالتقييم
            if ($request->filled('evaluation')) {
                $query->where('evaluation', $request->evaluation);
            }

            $sessions = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 20));$sessions->getCollection()->transform(function ($session) {
                return [
                    'id' => $session->id,
                    'تاريخ_الجلسة' => $session->created_at->format('Y-m-d'),
                    'نوع_التسميع' => $session->recitation_type ?? 'غير محدد',
                    'من_السورة' => $session->start_surah_number ?? 0,
                    'من_الآية' => $session->start_verse ?? 0,
                    'إلى_السورة' => $session->end_surah_number ?? 0,
                    'إلى_الآية' => $session->end_verse ?? 0,
                    'الدرجة' => $session->grade ?? 0,
                    'التقييم' => $session->evaluation ?? 'غير محدد',
                    'تقييم_الأداء' => ($session->grade ?? 0) >= 8 ? 'ممتاز' : 
                                    (($session->grade ?? 0) >= 6 ? 'جيد' : 'يحتاج تحسين'),
                    'ملاحظات' => $session->teacher_notes ?? 'لا توجد ملاحظات'
                ];
            });return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب جلسات تسميع الطالب بنجاح',
                'اسم_الطالب' => $student->name,
                'رقم_الهوية' => $student->identity_number,
                'البيانات' => $sessions->items(),
                'معلومات_الصفحة' => [
                    'الصفحة_الحالية' => $sessions->currentPage(),
                    'إجمالي_الصفحات' => $sessions->lastPage(),
                    'إجمالي_العناصر' => $sessions->total(),
                    'عناصر_الصفحة' => $sessions->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب جلسات تسميع الطالب',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض منهج الطالب اليومي
     */
    public function studentDailyCurriculum($id): JsonResponse
    {
        try {
            $student = Student::with([
                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ')
                          ->with(['curriculum', 'curriculumLevel']);
                },
                'curricula.progress' => function ($query) {
                    $query->where('status', 'قيد التنفيذ')
                          ->with('curriculumPlan')
                          ->orderBy('created_at', 'desc');
                }
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الطالب غير موجود'
                ], 404);
            }

            $activeCurriculum = $student->curricula->first();
            
            if (!$activeCurriculum) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'لا يوجد منهج نشط للطالب'
                ], 404);
            }

            // البحث عن الخطة اليومية الحالية
            $currentProgress = $activeCurriculum->progress->first();
            
            if (!$currentProgress || !$currentProgress->curriculumPlan) {
                // إنشاء خطة جديدة إذا لم توجد
                $nextPlan = DB::table('curriculum_plans')
                    ->where('curriculum_id', $activeCurriculum->curriculum_id)
                    ->orderBy('id')
                    ->first();
                    
                if ($nextPlan) {
                    $newProgress = DB::table('student_curriculum_progress')->insert([
                        'student_curriculum_id' => $activeCurriculum->id,
                        'curriculum_plan_id' => $nextPlan->id,
                        'status' => 'قيد التنفيذ',
                        'completion_percentage' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // إعادة تحميل البيانات
                    $currentProgress = $activeCurriculum->progress()->with('curriculumPlan')->first();
                }
            }

            if (!$currentProgress) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'لا توجد خطط متاحة في المنهج'
                ], 404);
            }

            $plan = $currentProgress->curriculumPlan;
            
            // تحليل محتوى الخطة
            $content = json_decode($plan->content, true) ?? [];
            
            // إعداد المنهج اليومي
            $dailyCurriculum = [
                'اليوم' => now()->format('Y-m-d'),
                'اسم_اليوم' => now()->translatedFormat('l'),
                'التاريخ_الهجري' => now()->format('Y-m-d'), // يمكن تطوير هذا لاحقاً
                'نوع_الخطة' => $plan->plan_type,
                'المنهج' => $activeCurriculum->curriculum->name,
                'المستوى' => $activeCurriculum->curriculumLevel->name ?? 'غير محدد',
                'حالة_التقدم' => $currentProgress->status,
                'نسبة_الإكمال' => $currentProgress->completion_percentage
            ];

            // تحديد محتوى اليوم بناءً على نوع الخطة
            switch ($plan->plan_type) {
                case 'الدرس':
                    $dailyCurriculum['حفظ_جديد'] = [
                        'النوع' => 'حفظ جديد',
                        'المحتوى' => $content['memorization'] ?? 'سورة الفاتحة الآيات 1-5',
                        'السورة' => $content['surah'] ?? 'الفاتحة',
                        'من_آية' => $content['start_verse'] ?? 1,
                        'إلى_آية' => $content['end_verse'] ?? 5,
                        'عدد_الآيات' => ($content['end_verse'] ?? 5) - ($content['start_verse'] ?? 1) + 1,
                        'مكتمل' => false
                    ];
                    break;
                    
                case 'المراجعة الصغرى':
                    $dailyCurriculum['مراجعة_صغرى'] = [
                        'النوع' => 'مراجعة صغرى',
                        'المحتوى' => $content['review'] ?? 'سورة الناس الآيات 1-3',
                        'السورة' => $content['surah'] ?? 'الناس',
                        'من_آية' => $content['start_verse'] ?? 1,
                        'إلى_آية' => $content['end_verse'] ?? 3,
                        'عدد_الآيات' => ($content['end_verse'] ?? 3) - ($content['start_verse'] ?? 1) + 1,
                        'مكتمل' => false
                    ];
                    break;
                    
                case 'المراجعة الكبرى':
                    $dailyCurriculum['مراجعة_كبرى'] = [
                        'النوع' => 'مراجعة كبرى',
                        'المحتوى' => $content['major_review'] ?? 'سورة الإخلاص الآيات 1-4، سورة الفلق الآيات 1-5',
                        'السور_المتعددة' => [
                            [
                                'السورة' => 'الإخلاص',
                                'من_آية' => 1,
                                'إلى_آية' => 4,
                                'عدد_الآيات' => 4
                            ],
                            [
                                'السورة' => 'الفلق', 
                                'من_آية' => 1,
                                'إلى_آية' => 5,
                                'عدد_الآيات' => 5
                            ]
                        ],
                        'مكتمل' => false
                    ];
                    break;
            }

            // الحصول على آخر جلسة تسميع
            $lastRecitation = RecitationSession::where('student_id', $id)
                ->orderBy('created_at', 'desc')
                ->first();

            $recitationStatus = [
                'آخر_تسميع' => $lastRecitation ? $lastRecitation->created_at->format('Y-m-d H:i') : 'لم يتم التسميع بعد',
                'درجة_آخر_تسميع' => $lastRecitation ? $lastRecitation->grade : null,
                'تقييم_آخر_تسميع' => $lastRecitation ? $lastRecitation->evaluation : null,
                'جاهز_للتسميع' => true,
                'يمكن_الانتقال_لليوم_التالي' => $currentProgress->status === 'مكتمل'
            ];

            // الخطة التالية
            $nextPlan = DB::table('curriculum_plans')
                ->where('curriculum_id', $activeCurriculum->curriculum_id)
                ->where('id', '>', $plan->id)
                ->orderBy('id')
                ->first();

            $nextDay = [
                'يوجد_خطة_تالية' => $nextPlan ? true : false,
                'نوع_الخطة_التالية' => $nextPlan ? $nextPlan->plan_type : null,
                'تاريخ_متوقع_للانتقال' => now()->addDay()->format('Y-m-d')
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب المنهج اليومي بنجاح',
                'معلومات_الطالب' => [
                    'الاسم' => $student->name,
                    'رقم_الطالب' => $student->student_number
                ],
                'المنهج_اليومي' => $dailyCurriculum,
                'حالة_التسميع' => $recitationStatus,
                'اليوم_التالي' => $nextDay,
                'إرشادات' => [
                    'كيفية_التسميع' => 'اقرأ المحتوى المطلوب بوضوح وتركيز',
                    'كيفية_الانتقال' => 'بعد اكتمال التسميع، استخدم API إكمال التسميع للانتقال لليوم التالي',
                    'ملاحظة' => 'يجب إكمال تسميع اليوم الحالي قبل الانتقال لليوم التالي'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب المنهج اليومي',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إكمال تسميع اليوم والانتقال لليوم التالي
     */
    public function completeDailyRecitation($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:teachers,id',
                'recitation_type' => 'required|in:حفظ,مراجعة صغرى,مراجعة كبرى',
                'grade' => 'required|numeric|min:1|max:10',
                'evaluation' => 'required|in:ممتاز,جيد جداً,جيد,مقبول,ضعيف',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'بيانات غير صحيحة',
                    'أخطاء' => $validator->errors()
                ], 422);
            }

            $student = Student::with([
                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ');
                }
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الطالب غير موجود'
                ], 404);
            }

            $activeCurriculum = $student->curricula->first();
            
            if (!$activeCurriculum) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'لا يوجد منهج نشط للطالب'
                ], 404);
            }

            DB::beginTransaction();
            
            try {
                // تسجيل جلسة التسميع
                $recitationSession = RecitationSession::create([
                    'student_id' => $id,
                    'teacher_id' => $request->teacher_id,
                    'recitation_type' => $request->recitation_type,
                    'grade' => $request->grade,
                    'evaluation' => $request->evaluation,
                    'notes' => $request->notes,
                    'session_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // تحديث تقدم الطالب في الخطة الحالية
                $currentProgress = $activeCurriculum->progress()
                    ->where('status', 'قيد التنفيذ')
                    ->first();

                if ($currentProgress) {
                    $currentProgress->update([
                        'status' => 'مكتمل',
                        'completion_percentage' => 100,
                        'updated_at' => now()
                    ]);
                }

                // البحث عن الخطة التالية
                $currentPlan = $currentProgress ? $currentProgress->curriculumPlan : null;
                $nextPlan = null;
                
                if ($currentPlan) {
                    $nextPlan = DB::table('curriculum_plans')
                        ->where('curriculum_id', $activeCurriculum->curriculum_id)
                        ->where('id', '>', $currentPlan->id)
                        ->orderBy('id')
                        ->first();
                }

                $message = 'تم تسجيل التسميع بنجاح';
                $nextDayInfo = null;

                // إنشاء تقدم جديد للخطة التالية
                if ($nextPlan) {
                    DB::table('student_curriculum_progress')->insert([
                        'student_curriculum_id' => $activeCurriculum->id,
                        'curriculum_plan_id' => $nextPlan->id,
                        'status' => 'قيد التنفيذ',
                        'completion_percentage' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $message .= ' وتم الانتقال للخطة التالية';
                    $nextDayInfo = [
                        'انتقل_للخطة_التالية' => true,
                        'نوع_الخطة_التالية' => $nextPlan->plan_type,
                        'محتوى_الخطة_التالية' => json_decode($nextPlan->content, true)
                    ];
                } else {
                    // انتهاء المنهج
                    $activeCurriculum->update([
                        'status' => 'مكتمل',
                        'completion_percentage' => 100
                    ]);
                    
                    $message .= ' وتم إكمال المنهج بالكامل!';
                    $nextDayInfo = [
                        'انتهى_المنهج' => true,
                        'رسالة_التهنئة' => 'تهانينا! لقد أكمل الطالب المنهج بنجاح'
                    ];
                }

                DB::commit();

                return response()->json([
                    'نجح' => true,
                    'رسالة' => $message,
                    'تفاصيل_التسميع' => [
                        'رقم_الجلسة' => $recitationSession->id,
                        'التاريخ' => $recitationSession->created_at->format('Y-m-d H:i'),
                        'الدرجة' => $request->grade,
                        'التقييم' => $request->evaluation,
                        'نوع_التسميع' => $request->recitation_type
                    ],
                    'معلومات_اليوم_التالي' => $nextDayInfo
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء تسجيل التسميع',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على منهج الطالب اليومي (الحفظ + المراجعة الصغرى + المراجعة الكبرى)
     */
    public function getDailyCurriculum($studentId): JsonResponse
    {        try {
            $student = Student::with([
                'mosque:id,name',                'curricula' => function ($query) {
                    $query->where('status', 'قيد التنفيذ')
                          ->with(['curriculum', 'level']);
                }
            ])->findOrFail($studentId);if ($student->curricula->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد منهج نشط للطالب'
                ], 404);
            }

            $activeCurriculum = $student->curricula->first();

            // البحث عن التقدم الحالي للطالب
            $currentProgress = DB::table('student_curriculum_progress')
                ->where('student_curriculum_id', $activeCurriculum->id)
                ->where('status', 'قيد التنفيذ')
                ->first();

            if (!$currentProgress) {
                // إنشاء أول تقدم للطالب
                $firstPlan = DB::table('curriculum_plans')
                    ->where('curriculum_id', $activeCurriculum->curriculum_id)
                    ->orderBy('id')
                    ->first();

                if ($firstPlan) {
                    $currentProgress = (object) [
                        'curriculum_plan_id' => $firstPlan->id,
                        'status' => 'قيد التنفيذ'
                    ];                    DB::table('student_curriculum_progress')->insert([
                        'student_curriculum_id' => $activeCurriculum->id,
                        'curriculum_plan_id' => $firstPlan->id,
                        'start_date' => now()->toDateString(),
                        'status' => 'قيد التنفيذ',
                        'completion_percentage' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // الحصول على خطط اليوم (الحفظ + المراجعة)
            $dailyPlans = DB::table('curriculum_plans')
                ->where('curriculum_id', $activeCurriculum->curriculum_id)
                ->where('id', '>=', $currentProgress->curriculum_plan_id)
                ->limit(3) // الحفظ + مراجعة صغرى + مراجعة كبرى
                ->get();

            $todayCurriculum = [
                'memorization' => null,
                'minor_review' => null,
                'major_review' => null
            ];

            foreach ($dailyPlans as $index => $plan) {
                $planData = [
                    'id' => $plan->id,
                    'type' => $plan->plan_type,
                    'content' => $plan->content,
                    'expected_days' => $plan->expected_days
                ];

                if ($index === 0) {
                    $todayCurriculum['memorization'] = $planData;
                } elseif ($index === 1) {
                    $todayCurriculum['minor_review'] = $planData;
                } elseif ($index === 2) {
                    $todayCurriculum['major_review'] = $planData;
                }
            }            // التحقق من جلسات التسميع اليوم
            $todayRecitations = RecitationSession::where('student_id', $studentId)
                ->whereDate('created_at', today())
                ->get()
                ->groupBy('recitation_type');return response()->json([
                'success' => true,                'data' => [
                    'student' => [
                        'name' => $student->name,
                        'mosque' => $student->mosque->name ?? null
                    ],                    'current_curriculum' => [
                        'name' => $activeCurriculum->curriculum->name ?? null,
                        'level' => $activeCurriculum->level->name ?? null,
                        'completion_percentage' => $activeCurriculum->completion_percentage ?? 0
                    ],
                    'daily_curriculum' => $todayCurriculum,                    'today_recitations' => [
                        'memorization' => $todayRecitations->get('حفظ', collect())->first(),
                        'minor_review' => $todayRecitations->get('مراجعة صغرى', collect())->first(),
                        'major_review' => $todayRecitations->get('مراجعة كبرى', collect())->first()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنهج اليومي',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إكمال تسميع اليوم والانتقال لليوم التالي
     */
    public function completeRecitation(Request $request, $studentId): JsonResponse
    {        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'recitation_type' => 'required|in:حفظ,مراجعة صغرى,مراجعة كبرى',
            'start_surah_number' => 'required|integer|min:1|max:114',
            'start_verse' => 'required|integer|min:1',
            'end_surah_number' => 'required|integer|min:1|max:114',
            'end_verse' => 'required|integer|min:1',
            'grade' => 'required|numeric|min:0|max:10',
            'evaluation' => 'required|in:ممتاز,جيد جداً,جيد,مقبول,ضعيف',
            'notes' => 'nullable|string'        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {            // تسجيل جلسة التسميع
            $recitationSession = RecitationSession::create([
                'student_id' => $studentId,
                'teacher_id' => $request->teacher_id,
                'recitation_type' => $request->recitation_type,
                'start_surah_number' => $request->start_surah_number,
                'start_verse' => $request->start_verse,
                'end_surah_number' => $request->end_surah_number,
                'end_verse' => $request->end_verse,
                'grade' => $request->grade,
                'evaluation' => $request->evaluation,
                'teacher_notes' => $request->notes ?? ''
            ]);

            // إذا كان هذا تسميع حفظ جديد وحصل على درجة جيدة، ننتقل لليوم التالي
            if ($request->recitation_type === 'حفظ' && $request->grade >= 7) {                $student = Student::with('curricula')->findOrFail($studentId);
                $activeCurriculum = $student->curricula->where('status', 'قيد التنفيذ')->first();

                if ($activeCurriculum) {
                    // تحديث التقدم الحالي كمكتمل
                    DB::table('student_curriculum_progress')
                        ->where('student_curriculum_id', $activeCurriculum->id)
                        ->where('status', 'قيد التنفيذ')
                        ->update([
                            'status' => 'مكتمل',
                            'completion_percentage' => 100,
                            'updated_at' => now()
                        ]);

                    // البحث عن الخطة التالية
                    $currentProgress = DB::table('student_curriculum_progress')
                        ->where('student_curriculum_id', $activeCurriculum->id)
                        ->where('status', 'مكتمل')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($currentProgress) {
                        $nextPlan = DB::table('curriculum_plans')
                            ->where('curriculum_id', $activeCurriculum->curriculum_id)
                            ->where('id', '>', $currentProgress->curriculum_plan_id)
                            ->orderBy('id')
                            ->first();                        if ($nextPlan) {
                            // إنشاء تقدم جديد للخطة التالية
                            DB::table('student_curriculum_progress')->insert([
                                'student_curriculum_id' => $activeCurriculum->id,
                                'curriculum_plan_id' => $nextPlan->id,
                                'start_date' => now()->toDateString(),
                                'status' => 'قيد التنفيذ',
                                'completion_percentage' => 0,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل التسميع بنجاح',
                'data' => [
                    'recitation_session' => $recitationSession,
                    'moved_to_next_day' => $request->recitation_type === 'حفظ' && $request->grade >= 7
                ]
            ]);        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }    /**
     * آخر جلسة تسميع للطالب مع دعم الفلاتر
     */
    public function getLastRecitation($id, Request $request): JsonResponse
    {
        try {
            $student = Student::find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }            $query = $student->recitationSessions();

            // فلترة بنوع التسميع
            if ($request->filled('recitation_type')) {
                $query->where('recitation_type', $request->recitation_type);
            }

            // فلترة بالحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلترة بالتقييم
            if ($request->filled('evaluation')) {
                $query->where('evaluation', $request->evaluation);
            }

            // فلترة بدرجة الجودة الدنيا
            if ($request->filled('min_grade')) {
                $query->where('grade', '>=', $request->min_grade);
            }

            // فلترة بتاريخ أحدث من
            if ($request->filled('since_date')) {
                $query->where('created_at', '>=', $request->since_date);
            }

            $lastSession = $query->orderBy('created_at', 'desc')
                ->first();

            if (!$lastSession) {
                return response()->json([
                    'success' => true,
                    'message' => 'لم يتم العثور على جلسات تسميع للطالب',
                    'data' => null
                ]);
            }

            $sessionData = [
                'id' => $lastSession->id,
                'student_id' => $lastSession->student_id,
                'teacher_id' => $lastSession->teacher_id,
                'session_date' => $lastSession->created_at->format('Y-m-d'),
                'session_time' => $lastSession->created_at->format('H:i:s'),
                'recitation_type' => $lastSession->recitation_type ?? 'غير محدد',
                'surah_range' => [
                    'start_surah' => $lastSession->start_surah_number ?? 0,
                    'start_verse' => $lastSession->start_verse ?? 0,
                    'end_surah' => $lastSession->end_surah_number ?? 0,
                    'end_verse' => $lastSession->end_verse ?? 0
                ],
                'content_summary' => "سورة {$lastSession->start_surah_number} آية {$lastSession->start_verse} إلى سورة {$lastSession->end_surah_number} آية {$lastSession->end_verse}",
                'total_verses' => $lastSession->total_verses ?? 0,
                'grade' => floatval($lastSession->grade ?? 0),
                'evaluation' => $lastSession->evaluation ?? 'غير محدد',
                'status' => $lastSession->status ?? 'غير محدد',
                'has_errors' => $lastSession->has_errors ?? false,
                'teacher_notes' => $lastSession->teacher_notes ?? 'لا توجد ملاحظات',
                'performance_rating' => $this->getPerformanceRating($lastSession->grade ?? 0),
                'days_ago' => $lastSession->created_at->diffInDays(now())
            ];

            return response()->json([
                'success' => true,
                'message' => 'تم جلب آخر جلسة تسميع بنجاح',
                'student_name' => $student->name,
                'data' => $sessionData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب آخر جلسة تسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديد تقييم الأداء بناءً على الدرجة
     */
    private function getPerformanceRating($grade)
    {
        if ($grade >= 9) return 'ممتاز';
        if ($grade >= 8) return 'جيد جداً';
        if ($grade >= 7) return 'جيد';
        if ($grade >= 6) return 'مقبول';
        return 'يحتاج تحسين';
    }
}
