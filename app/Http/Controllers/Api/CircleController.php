<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranCircle;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\RecitationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * API الحلقات - عرض وإدارة بيانات الحلقات والمعلمين والطلاب
 */
class CircleController extends Controller
{
    /**
     * عرض قائمة جميع الحلقات
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = QuranCircle::with([
                'teacher.user:id,name,phone',
                'students:id,name,student_number,phone',
                'circleGroup:id,name',
                'mosque:id,name'
            ]);

            // فلترة حسب المسجد
            if ($request->filled('mosque_id')) {
                $query->where('mosque_id', $request->mosque_id);
            }

            // فلترة حسب المعلم
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            // فلترة حسب المستوى
            if ($request->filled('grade_level')) {
                $query->where('grade_level', $request->grade_level);
            }

            // فلترة حسب حالة النشاط
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // البحث بالاسم
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $circles = $query->paginate($request->get('per_page', 15));

            $circles->getCollection()->transform(function ($circle) {
                return [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'المستوى' => $circle->grade_level,
                    'المجموعة' => $circle->circleGroup->name ?? 'غير محدد',
                    'المسجد' => $circle->mosque->name ?? 'غير محدد',
                    'المعلم' => [
                        'id' => $circle->teacher->id ?? null,
                        'الاسم' => $circle->teacher->user->name ?? 'غير محدد',
                        'رقم_الهاتف' => $circle->teacher->user->phone ?? 'غير محدد'
                    ],
                    'عدد_الطلاب' => $circle->students->count(),
                    'قائمة_الطلاب' => $circle->students->take(5)->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'الاسم' => $student->name,
                            'رقم_الطالب' => $student->student_number
                        ];
                    }),
                    'نشطة' => $circle->is_active ? 'نعم' : 'لا',
                    'السعة_القصوى' => $circle->max_capacity,
                    'تاريخ_الإنشاء' => $circle->created_at->format('Y-m-d'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب قائمة الحلقات بنجاح',
                'البيانات' => $circles->items(),
                'معلومات_الصفحة' => [
                    'الصفحة_الحالية' => $circles->currentPage(),
                    'إجمالي_الصفحات' => $circles->lastPage(),
                    'إجمالي_العناصر' => $circles->total(),
                    'عناصر_الصفحة' => $circles->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب بيانات الحلقات',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل حلقة محددة
     */
    public function show($id): JsonResponse
    {
        try {
            $circle = QuranCircle::with([
                'teacher.user:id,name,email,phone',
                'teacher.mosque:id,name',
                'students' => function ($query) {
                    $query->with([
                        'curriculum:id,student_id,current_surah,current_ayah,memorized_pages',
                        'progress' => function ($q) {
                            $q->orderBy('session_date', 'desc')->limit(3);
                        },
                        'recitationSessions' => function ($q) {
                            $q->orderBy('session_date', 'desc')->limit(3);
                        }
                    ]);
                },
                'circleGroup:id,name,description',
                'mosque:id,name,address'
            ])->find($id);

            if (!$circle) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الحلقة غير موجودة'
                ], 404);
            }

            // حساب الإحصائيات
            $totalStudents = $circle->students->count();
            $activeStudents = $circle->students->where('is_active', true)->count();
            
            // إحصائيات التقدم
            $avgMemorizedPages = $circle->students->avg(function ($student) {
                return $student->curriculum->memorized_pages ?? 0;
            });

            // إحصائيات جلسات التسميع
            $recitationStats = RecitationSession::whereIn('student_id', $circle->students->pluck('id'))
                ->selectRaw('
                    COUNT(*) as total_sessions,
                    AVG(quality_score) as avg_quality,
                    SUM(pages_recited) as total_pages
                ')->first();

            $data = [
                'معلومات_الحلقة' => [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'المستوى' => $circle->grade_level,
                    'الوصف' => $circle->description ?? 'لا يوجد وصف',
                    'المجموعة' => [
                        'id' => $circle->circleGroup->id ?? null,
                        'الاسم' => $circle->circleGroup->name ?? 'غير محدد',
                        'الوصف' => $circle->circleGroup->description ?? 'لا يوجد وصف'
                    ],
                    'المسجد' => [
                        'id' => $circle->mosque->id ?? null,
                        'الاسم' => $circle->mosque->name ?? 'غير محدد',
                        'العنوان' => $circle->mosque->address ?? 'غير محدد'
                    ],
                    'نشطة' => $circle->is_active ? 'نعم' : 'لا',
                    'السعة_القصوى' => $circle->max_capacity,
                    'تاريخ_الإنشاء' => $circle->created_at->format('Y-m-d H:i'),
                ],
                'المعلم' => [
                    'id' => $circle->teacher->id ?? null,
                    'الاسم' => $circle->teacher->user->name ?? 'غير محدد',
                    'البريد_الإلكتروني' => $circle->teacher->user->email ?? 'غير محدد',
                    'رقم_الهاتف' => $circle->teacher->user->phone ?? 'غير محدد',
                    'رقم_الهوية' => $circle->teacher->id_number ?? 'غير محدد',
                ],
                'إحصائيات' => [
                    'عدد_الطلاب' => $totalStudents,
                    'الطلاب_النشطون' => $activeStudents,
                    'متوسط_الصفحات_المحفوظة' => round($avgMemorizedPages, 2),
                    'إجمالي_جلسات_التسميع' => $recitationStats->total_sessions ?? 0,
                    'متوسط_جودة_التسميع' => round($recitationStats->avg_quality ?? 0, 2),
                    'إجمالي_الصفحات_المسمعة' => $recitationStats->total_pages ?? 0,
                ],
                'الطلاب' => $circle->students->map(function ($student) {
                    $latestProgress = $student->progress->first();
                    $latestRecitation = $student->recitationSessions->first();
                    
                    return [
                        'id' => $student->id,
                        'الاسم' => $student->name,
                        'رقم_الطالب' => $student->student_number,
                        'رقم_الهاتف' => $student->phone,
                        'نشط' => $student->is_active ? 'نعم' : 'لا',
                        'المنهج_الحالي' => [
                            'السورة' => $student->curriculum->current_surah ?? 'غير محدد',
                            'الآية' => $student->curriculum->current_ayah ?? 'غير محدد',
                            'الصفحات_المحفوظة' => $student->curriculum->memorized_pages ?? 0,
                        ],
                        'آخر_تقدم' => $latestProgress ? [
                            'تاريخ_الجلسة' => $latestProgress->session_date->format('Y-m-d'),
                            'الصفحات_المحفوظة' => $latestProgress->pages_memorized,
                            'درجة_الجودة' => $latestProgress->quality_score,
                        ] : null,
                        'آخر_تسميع' => $latestRecitation ? [
                            'تاريخ_الجلسة' => $latestRecitation->session_date->format('Y-m-d'),
                            'السورة' => $latestRecitation->surah_name,
                            'عدد_الصفحات' => $latestRecitation->pages_recited,
                            'درجة_الجودة' => $latestRecitation->quality_score,
                        ] : null,
                        'تاريخ_الانضمام' => $student->created_at->format('Y-m-d'),
                    ];
                })
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب تفاصيل الحلقة بنجاح',
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب تفاصيل الحلقة',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض إحصائيات حلقة محددة
     */
    public function circleStats($id): JsonResponse
    {
        try {
            $circle = QuranCircle::with(['students', 'teacher'])->find($id);

            if (!$circle) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'الحلقة غير موجودة'
                ], 404);
            }

            $studentIds = $circle->students->pluck('id');

            // إحصائيات الطلاب
            $studentStats = [
                'إجمالي_الطلاب' => $circle->students->count(),
                'الطلاب_النشطون' => $circle->students->where('is_active', true)->count(),
                'الطلاب_غير_النشطين' => $circle->students->where('is_active', false)->count(),
                'متوسط_العمر' => round($circle->students->filter(function ($student) {
                    return $student->birth_date;
                })->avg(function ($student) {
                    return $student->birth_date->age;
                }), 1),
            ];

            // إحصائيات التقدم
            $progressStats = [
                'متوسط_الصفحات_المحفوظة' => round($circle->students->avg(function ($student) {
                    return $student->curriculum->memorized_pages ?? 0;
                }), 2),
                'أعلى_تقدم' => $circle->students->max(function ($student) {
                    return $student->curriculum->memorized_pages ?? 0;
                }),
                'أقل_تقدم' => $circle->students->min(function ($student) {
                    return $student->curriculum->memorized_pages ?? 0;
                }),
                'عدد_الطلاب_المتفوقين' => $circle->students->filter(function ($student) {
                    return ($student->curriculum->memorized_pages ?? 0) > 100;
                })->count(),
            ];

            // إحصائيات التسميع
            $recitationStats = RecitationSession::whereIn('student_id', $studentIds)
                ->selectRaw('
                    COUNT(*) as total_sessions,
                    AVG(quality_score) as avg_quality,
                    SUM(pages_recited) as total_pages,
                    COUNT(CASE WHEN quality_score >= 90 THEN 1 END) as excellent_sessions,
                    COUNT(CASE WHEN quality_score >= 70 AND quality_score < 90 THEN 1 END) as good_sessions,
                    COUNT(CASE WHEN quality_score < 70 THEN 1 END) as needs_improvement_sessions
                ')->first();

            // إحصائيات الحضور
            $attendanceStats = [
                'نسبة_الحضور_الشهر_الحالي' => $studentIds->count() > 0 ? round(
                    $circle->students->avg(function ($student) {
                        $totalDays = $student->attendances()
                            ->whereYear('date', now()->year)
                            ->whereMonth('date', now()->month)
                            ->count();
                        $presentDays = $student->attendances()
                            ->whereYear('date', now()->year)
                            ->whereMonth('date', now()->month)
                            ->where('is_present', true)
                            ->count();
                        return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
                    }), 2
                ) : 0,
            ];

            // التقدم الشهري للحلقة (آخر 6 أشهر)
            $monthlyProgress = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $progress = RecitationSession::whereIn('student_id', $studentIds)
                    ->whereYear('session_date', $date->year)
                    ->whereMonth('session_date', $date->month)
                    ->selectRaw('
                        COUNT(*) as sessions_count,
                        SUM(pages_recited) as pages_count,
                        AVG(quality_score) as avg_quality
                    ')->first();

                $monthlyProgress[] = [
                    'الشهر' => $date->format('Y-m'),
                    'اسم_الشهر' => $date->translatedFormat('F Y'),
                    'عدد_الجلسات' => $progress->sessions_count ?? 0,
                    'عدد_الصفحات' => $progress->pages_count ?? 0,
                    'متوسط_الجودة' => round($progress->avg_quality ?? 0, 2),
                ];
            }

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب إحصائيات الحلقة بنجاح',
                'اسم_الحلقة' => $circle->name,
                'المعلم' => $circle->teacher->user->name ?? 'غير محدد',
                'إحصائيات' => [
                    'الطلاب' => $studentStats,
                    'التقدم' => $progressStats,
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
                    'التقدم_الشهري' => $monthlyProgress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب إحصائيات الحلقة',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض قائمة الحلقات حسب المعلم
     */
    public function circlesByTeacher($teacherId): JsonResponse
    {
        try {
            $teacher = Teacher::with('user:id,name')->find($teacherId);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            $circles = QuranCircle::where('teacher_id', $teacherId)
                ->with([
                    'students:id,name,student_number',
                    'circleGroup:id,name',
                    'mosque:id,name'
                ])
                ->get();

            $circlesData = $circles->map(function ($circle) {
                return [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'المستوى' => $circle->grade_level,
                    'المجموعة' => $circle->circleGroup->name ?? 'غير محدد',
                    'المسجد' => $circle->mosque->name ?? 'غير محدد',
                    'عدد_الطلاب' => $circle->students->count(),
                    'نشطة' => $circle->is_active ? 'نعم' : 'لا',
                    'السعة_القصوى' => $circle->max_capacity,
                    'قائمة_الطلاب' => $circle->students->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'الاسم' => $student->name,
                            'رقم_الطالب' => $student->student_number
                        ];
                    }),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب حلقات المعلم بنجاح',
                'اسم_المعلم' => $teacher->user->name ?? 'غير محدد',
                'عدد_الحلقات' => $circles->count(),
                'إجمالي_الطلاب' => $circles->sum(function ($circle) {
                    return $circle->students->count();
                }),
                'الحلقات' => $circlesData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب حلقات المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض أفضل الحلقات من حيث الأداء
     */
    public function topPerformingCircles(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);

            $circles = QuranCircle::with([
                'teacher.user:id,name',
                'students',
                'mosque:id,name'
            ])
            ->where('is_active', true)
            ->get()
            ->map(function ($circle) {
                $studentIds = $circle->students->pluck('id');
                
                // حساب متوسط جودة التسميع
                $avgQuality = RecitationSession::whereIn('student_id', $studentIds)
                    ->avg('quality_score') ?? 0;
                
                // حساب متوسط الصفحات المحفوظة
                $avgMemorizedPages = $circle->students->avg(function ($student) {
                    return $student->curriculum->memorized_pages ?? 0;
                });
                
                // حساب نسبة الحضور
                $attendanceRate = $studentIds->count() > 0 ? $circle->students->avg(function ($student) {
                    $totalDays = $student->attendances()->count();
                    $presentDays = $student->attendances()->where('is_present', true)->count();
                    return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
                }) : 0;
                
                // حساب نقاط الأداء الإجمالية
                $performanceScore = ($avgQuality * 0.4) + (($avgMemorizedPages / 604 * 100) * 0.4) + ($attendanceRate * 0.2);
                
                return [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'المعلم' => $circle->teacher->user->name ?? 'غير محدد',
                    'المسجد' => $circle->mosque->name ?? 'غير محدد',
                    'عدد_الطلاب' => $circle->students->count(),
                    'متوسط_جودة_التسميع' => round($avgQuality, 2),
                    'متوسط_الصفحات_المحفوظة' => round($avgMemorizedPages, 2),
                    'نسبة_الحضور' => round($attendanceRate, 2),
                    'نقاط_الأداء' => round($performanceScore, 2),
                ];
            })
            ->sortByDesc('نقاط_الأداء')
            ->take($limit)
            ->values();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب أفضل الحلقات بنجاح',
                'البيانات' => $circles,
                'ملاحظة' => 'نقاط الأداء محسوبة بناءً على: جودة التسميع (40%) + التقدم في الحفظ (40%) + نسبة الحضور (20%)'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب أفضل الحلقات',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }
}
