<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\QuranCircle;
use App\Models\Student;
use App\Models\RecitationSession;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\TeacherIncentive;
use App\Models\TeacherSalary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * API المعلمين - عرض وإدارة بيانات المعلمين والحلقات والطلاب
 */
class TeacherController extends Controller
{    /**
     * عرض قائمة جميع المعلمين مع معلوماتهم الأساسية
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Teacher::with([
                'mosque:id,name',
                'quranCircle:id,name,circle_type,circle_status'
            ]);

            // فلترة حسب المسجد
            if ($request->filled('mosque_id')) {
                $query->where('mosque_id', $request->mosque_id);
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

            $teachers = $query->paginate($request->get('per_page', 15));

            $teachers->getCollection()->transform(function ($teacher) {
                // حساب عدد الطلاب في حلقة المعلم
                $studentsCount = 0;
                if ($teacher->quranCircle) {
                    $studentsCount = Student::where('quran_circle_id', $teacher->quranCircle->id)->count();
                }

                return [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name ?? 'غير محدد',
                    'البريد_الإلكتروني' => 'غير محدد',
                    'رقم_الهاتف' => $teacher->phone ?? 'غير محدد',
                    'رقم_الهوية' => $teacher->identity_number,
                    'المسجد' => $teacher->mosque->name ?? 'غير محدد',
                    'نشط' => $teacher->is_active ? 'نعم' : 'لا',
                    'تاريخ_التعيين' => $teacher->start_date?->format('Y-m-d'),
                    'الحلقة' => $teacher->quranCircle ? [
                        'id' => $teacher->quranCircle->id,                        'اسم' => $teacher->quranCircle->name,
                        'النوع' => $teacher->quranCircle->circle_type ?? 'غير محدد'
                    ] : null,
                    'عدد_الطلاب' => $studentsCount,
                    'إجمالي_الراتب' => $teacher->total_salary,
                    'تاريخ_الانضمام' => $teacher->created_at->format('Y-m-d H:i'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب قائمة المعلمين بنجاح',
                'البيانات' => $teachers->items(),
                'معلومات_الصفحة' => [
                    'الصفحة_الحالية' => $teachers->currentPage(),
                    'إجمالي_الصفحات' => $teachers->lastPage(),
                    'إجمالي_العناصر' => $teachers->total(),
                    'عناصر_الصفحة' => $teachers->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب بيانات المعلمين',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }    /**
     * عرض تفاصيل معلم محدد مع جميع معلوماته
     */
    public function show($id): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'mosque:id,name,neighborhood,street',
                'quranCircle:id,name,circle_type'
            ])->find($id);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            // حساب عدد الطلاب في حلقة المعلم
            $totalStudents = 0;
            if ($teacher->quranCircle) {
                $totalStudents = Student::where('quran_circle_id', $teacher->quranCircle->id)->count();
            }            // إحصائيات بسيطة (مؤقت)
            $currentMonthAttendances = 0;
            $recitationStats = null;

            $data = [
                'معلومات_أساسية' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name ?? 'غير محدد',
                    'البريد_الإلكتروني' => $teacher->identity_number ?? 'غير محدد',
                    'رقم_الهاتف' => $teacher->phone ?? 'غير محدد',
                    'رقم_الهوية' => $teacher->identity_number,
                    'المسجد' => [
                        'id' => $teacher->mosque->id ?? null,
                        'الاسم' => $teacher->mosque->name ?? 'غير محدد',
                        'العنوان' => $teacher->mosque->address ?? 'غير محدد'
                    ],
                    'نشط' => $teacher->is_active ? 'نعم' : 'لا',
                    'تاريخ_التعيين' => $teacher->hire_date?->format('Y-m-d'),
                    'إجمالي_الراتب' => $teacher->total_salary,
                    'ملاحظات' => $teacher->notes ?? 'لا توجد ملاحظات',
                    'تاريخ_الانضمام' => $teacher->created_at->format('Y-m-d H:i'),
                ],
                'إحصائيات' => [
                    'الحلقة' => $teacher->quranCircle ? $teacher->quranCircle->name : 'لا توجد حلقة',
                    'إجمالي_الطلاب' => $totalStudents,
                    'حضور_الشهر_الحالي' => $currentMonthAttendances,
                    'إجمالي_جلسات_التسميع' => $recitationStats->total_sessions ?? 0,
                    'متوسط_جودة_التسميع' => round($recitationStats->avg_quality ?? 0, 2),
                    'إجمالي_الصفحات_المسمعة' => $recitationStats->total_pages ?? 0,
                ],
                'الحلقة' => $teacher->quranCircle ? [
                    'id' => $teacher->quranCircle->id,
                    'الاسم' => $teacher->quranCircle->name,
                    'المستوى' => $teacher->quranCircle->circle_type ?? 'غير محدد',
                    'عدد_الطلاب' => $totalStudents
                ] : null,                'سجل_الحضور_الأخير' => [],
                'الحوافز_الأخيرة' => [],
                'الرواتب_الأخيرة' => []
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب تفاصيل المعلم بنجاح',
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب تفاصيل المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض جميع حلقات معلم محدد مع تفاصيل الطلاب (يشمل التكليفات النشطة في مساجد متعددة)
     */
    public function teacherCircles($id): JsonResponse
    {
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            $allCircles = [];

            // 1. جلب الحلقة الأساسية للمعلم (من حقل quran_circle_id)
            if ($teacher->quranCircle) {
                $circle = $teacher->quranCircle->load(['students', 'mosque']);
                $allCircles[] = [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'نوع_الحلقة' => $circle->circle_type,
                    'حالة_الحلقة' => $circle->circle_status,
                    'فترة_الدراسة' => $circle->time_period,
                    'عدد_الطلاب' => $circle->students->count(),
                    'معرف_المسجد' => $circle->mosque_id,
                    'اسم_المسجد' => $circle->mosque ? $circle->mosque->name : 'غير محدد',
                    'حي_المسجد' => $circle->mosque ? $circle->mosque->neighborhood : 'غير محدد',
                    'نوع_التكليف' => 'أساسي',
                    'تفاصيل_الطلاب' => $circle->students->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'الاسم' => $student->name,
                            'رقم_الهوية' => $student->identity_number,
                            'رقم_الهاتف' => $student->phone,
                            'نشط' => $student->is_active ? 'نعم' : 'لا'
                        ];
                    })
                ];
            }

            // 2. جلب جميع التكليفات النشطة الأخرى للمعلم (من جدول teacher_circle_assignments)
            $activeAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $id)
                ->where('is_active', true)
                ->with(['circle.students', 'circle.mosque'])
                ->get();

            foreach ($activeAssignments as $assignment) {
                $circle = $assignment->circle;
                
                // تجنب التكرار - إذا كانت الحلقة موجودة أصلاً في الحلقة الأساسية
                $existsInMain = false;
                foreach ($allCircles as $existingCircle) {
                    if ($existingCircle['id'] == $circle->id) {
                        $existsInMain = true;
                        break;
                    }
                }

                if (!$existsInMain) {
                    $allCircles[] = [
                        'id' => $circle->id,
                        'اسم_الحلقة' => $circle->name,
                        'نوع_الحلقة' => $circle->circle_type,
                        'حالة_الحلقة' => $circle->circle_status,
                        'فترة_الدراسة' => $circle->time_period,
                        'عدد_الطلاب' => $circle->students->count(),
                        'معرف_المسجد' => $circle->mosque_id,
                        'اسم_المسجد' => $circle->mosque ? $circle->mosque->name : 'غير محدد',
                        'حي_المسجد' => $circle->mosque ? $circle->mosque->neighborhood : 'غير محدد',
                        'نوع_التكليف' => 'تكليف إضافي',
                        'تاريخ_بداية_التكليف' => $assignment->start_date,
                        'تاريخ_نهاية_التكليف' => $assignment->end_date ?? 'مفتوح',
                        'تفاصيل_الطلاب' => $circle->students->map(function ($student) {
                            return [
                                'id' => $student->id,
                                'الاسم' => $student->name,
                                'رقم_الهوية' => $student->identity_number,
                                'رقم_الهاتف' => $student->phone,
                                'نشط' => $student->is_active ? 'نعم' : 'لا'
                            ];
                        })
                    ];
                }
            }

            // إذا لم يتم العثور على أي حلقات
            if (empty($allCircles)) {
                return response()->json([
                    'نجح' => true,
                    'رسالة' => 'لا توجد حلقات مرتبطة بهذا المعلم',
                    'اسم_المعلم' => $teacher->name ?? 'غير محدد',
                    'البيانات' => []
                ], 200);
            }

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب حلقات المعلم بنجاح',
                'اسم_المعلم' => $teacher->name ?? 'غير محدد',
                'عدد_الحلقات' => count($allCircles),
                'البيانات' => $allCircles
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
     * عرض إحصائيات معلم محدد
     */
    public function teacherStats($id): JsonResponse
    {
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            // إحصائيات الحلقات والطلاب - using a more compatible approach
            $totalStudents = 0;
            $circlesCount = 0;
            
            if ($teacher->quranCircle) {
                $totalStudents = Student::where('quran_circle_id', $teacher->quranCircle->id)->count();
                $circlesCount = 1;
            }

            // إحصائيات الحضور
            $attendanceStats = [
                'الشهر_الحالي' => $teacher->attendances()
                    ->whereYear('date', now()->year)
                    ->whereMonth('date', now()->month)
                    ->count(),
                'الأسبوع_الحالي' => $teacher->attendances()
                    ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'إجمالي_هذا_العام' => $teacher->attendances()
                    ->whereYear('date', now()->year)
                    ->count()
            ];            // إحصائيات التسميع
            $recitationStats = RecitationSession::whereHas('student', function ($query) use ($teacher) {
                $query->where('quran_circle_id', $teacher->quran_circle_id);
            })->selectRaw('
                COUNT(*) as total_sessions,
                AVG(grade) as avg_quality,
                SUM(total_verses) as total_pages,
                COUNT(CASE WHEN grade >= 80 THEN 1 END) as excellent_sessions
            ')->first();

            // إحصائيات الرواتب والحوافز
            $salaryStats = [
                'إجمالي_الرواتب_هذا_العام' => $teacher->salaries()
                    ->whereYear('month', now()->year)
                    ->sum('net_salary'),
                'إجمالي_الحوافز_هذا_العام' => $teacher->incentives()
                    ->whereYear('date', now()->year)
                    ->sum('amount'),
                'متوسط_الراتب_الشهري' => $teacher->salaries()
                    ->whereYear('month', now()->year)
                    ->avg('net_salary'),
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب إحصائيات المعلم بنجاح',
                'اسم_المعلم' => $teacher->name ?? 'غير محدد',
                'الإحصائيات' => [
                    'الحلقات_والطلاب' => [
                        'عدد_الحلقات' => $circlesCount,
                        'إجمالي_الطلاب' => $totalStudents,
                        'الحلقات_النشطة' => $circlesCount > 0 && $teacher->quranCircle && $teacher->quranCircle->is_active ? 1 : 0,
                        'متوسط_الطلاب_لكل_حلقة' => $circlesCount > 0 ? round($totalStudents / $circlesCount, 2) : 0
                    ],
                    'الحضور' => $attendanceStats,
                    'التسميع' => [
                        'إجمالي_الجلسات' => $recitationStats->total_sessions ?? 0,
                        'متوسط_الجودة' => round($recitationStats->avg_quality ?? 0, 2),
                        'إجمالي_الصفحات_المسمعة' => $recitationStats->total_pages ?? 0,
                        'الجلسات_الممتازة' => $recitationStats->excellent_sessions ?? 0,
                        'نسبة_النجاح' => $recitationStats && $recitationStats->total_sessions > 0 
                            ? round(($recitationStats->excellent_sessions / $recitationStats->total_sessions) * 100, 2) 
                            : 0
                    ],
                    'المالية' => $salaryStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب إحصائيات المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض سجل حضور معلم محدد
     */
    public function teacherAttendance($id, Request $request): JsonResponse
    {
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            $query = $teacher->attendances();

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
                                ->paginate($request->get('per_page', 30));

            $attendances->getCollection()->transform(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'التاريخ' => $attendance->date->format('Y-m-d'),
                    'اليوم' => $attendance->date->translatedFormat('l'),
                    'وقت_الوصول' => $attendance->arrival_time?->format('H:i'),
                    'وقت_المغادرة' => $attendance->departure_time?->format('H:i'),                    'مدة_الحضور' => $attendance->arrival_time ? ($attendance->departure_time 
                        ? $attendance->arrival_time->diff($attendance->departure_time)->format('%h:%i')
                        : 'غير محدد') : 'غير محدد',
                    'حاضر' => $attendance->is_present ? 'نعم' : 'لا',
                    'متأخر' => $attendance->is_late ? 'نعم' : 'لا',
                    'ملاحظات' => $attendance->notes ?? 'لا توجد ملاحظات'
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب سجل حضور المعلم بنجاح',
                'اسم_المعلم' => $teacher->name ?? 'غير محدد',
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
                'رسالة' => 'حدث خطأ أثناء جلب سجل حضور المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض رواتب وحوافز معلم محدد
     */
    public function teacherFinancials($id, Request $request): JsonResponse
    {
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير موجود'
                ], 404);
            }

            // الرواتب
            $salariesQuery = $teacher->salaries();
            
            if ($request->filled('year')) {
                $salariesQuery->whereYear('month', $request->year);
            }

            $salaries = $salariesQuery->orderBy('month', 'desc')->get();

            // الحوافز
            $incentivesQuery = $teacher->incentives();
            
            if ($request->filled('year')) {
                $incentivesQuery->whereYear('date', $request->year);
            }

            $incentives = $incentivesQuery->orderBy('date', 'desc')->get();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب البيانات المالية للمعلم بنجاح',
                'اسم_المعلم' => $teacher->name ?? 'غير محدد',
                'البيانات_المالية' => [
                    'الرواتب' => $salaries->map(function ($salary) {
                        return [
                            'id' => $salary->id,
                            'الشهر' => $salary->month->format('Y-m'),
                            'الراتب_الأساسي' => $salary->base_salary,
                            'البدلات' => $salary->allowances,
                            'الخصومات' => $salary->deductions,
                            'صافي_الراتب' => $salary->net_salary,
                            'مدفوع' => $salary->is_paid ? 'نعم' : 'لا',
                            'تاريخ_الدفع' => $salary->payment_date?->format('Y-m-d'),
                            'ملاحظات' => $salary->notes ?? 'لا توجد ملاحظات'
                        ];
                    }),
                    'الحوافز' => $incentives->map(function ($incentive) {
                        return [
                            'id' => $incentive->id,
                            'التاريخ' => $incentive->date->format('Y-m-d'),
                            'النوع' => $incentive->type,
                            'المبلغ' => $incentive->amount,
                            'الوصف' => $incentive->description ?? 'غير محدد',
                            'تاريخ_الإضافة' => $incentive->created_at->format('Y-m-d')
                        ];
                    }),
                    'ملخص_مالي' => [
                        'إجمالي_الرواتب' => $salaries->sum('net_salary'),
                        'إجمالي_الحوافز' => $incentives->sum('amount'),
                        'الإجمالي_الكلي' => $salaries->sum('net_salary') + $incentives->sum('amount'),
                        'عدد_الأشهر_المدفوعة' => $salaries->where('is_paid', true)->count(),
                        'عدد_الأشهر_غير_المدفوعة' => $salaries->where('is_paid', false)->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب البيانات المالية للمعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على جميع الطلاب المرتبطين بالمعلم مع تفاصيل الحلقة والمسجد
     */
    public function getStudents($id): JsonResponse
    {
        return $this->getTeacherStudents($id);
    }

    /**
     * الحصول على المساجد التي يعمل بها المعلم مع طلاب كل مسجد
     */
    public function getTeacherMosques($id): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'mosque:id,name,neighborhood,street',
                'quranCircle.mosque:id,name,neighborhood,street'
            ])->findOrFail($id);

            $mosquesData = collect();

            // 1. المسجد الأساسي للمعلم
            if ($teacher->mosque) {
                $mainMosqueData = $this->buildMosqueData($teacher, $teacher->mosque, 'أساسي');
                if ($mainMosqueData) {
                    $mosquesData->push($mainMosqueData);
                }
            }

            // 2. المساجد من التكليفات الإضافية
            $activeAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $id)
                ->where('is_active', true)
                ->with(['circle.mosque:id,name,neighborhood,street'])
                ->get();

            foreach ($activeAssignments as $assignment) {
                $circle = $assignment->circle;
                if ($circle && $circle->mosque) {
                    // تحقق من عدم تكرار المسجد
                    $mosqueExists = $mosquesData->contains(function($mosque) use ($circle) {
                        return $mosque['معرف_المسجد'] == $circle->mosque->id;
                    });

                    if (!$mosqueExists) {
                        $assignmentMosqueData = $this->buildMosqueData($teacher, $circle->mosque, 'تكليف إضافي', $assignment);
                        if ($assignmentMosqueData) {
                            $mosquesData->push($assignmentMosqueData);
                        }
                    }
                }
            }

            // إحصائيات عامة
            $totalMosques = $mosquesData->count();
            $totalStudents = $mosquesData->sum('إحصائيات.عدد_الطلاب');
            $totalCircles = $mosquesData->sum('إحصائيات.عدد_الحلقات');

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب مساجد المعلم بنجاح',
                'بيانات_المعلم' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الهاتف' => $teacher->phone,
                ],
                'إحصائيات_عامة' => [
                    'عدد_المساجد' => $totalMosques,
                    'إجمالي_الطلاب' => $totalStudents,
                    'إجمالي_الحلقات' => $totalCircles,
                    'تاريخ_التحديث' => now()->format('Y-m-d H:i:s')
                ],
                'المساجد' => $mosquesData->values()->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'المعلم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ في جلب بيانات مساجد المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * بناء بيانات مسجد مع طلابه الخاصين بالمعلم
     */
    private function buildMosqueData($teacher, $mosque, $assignmentType, $assignment = null): ?array
    {
        // جلب الحلقات في هذا المسجد التي يعمل بها المعلم
        $circles = collect();
        
        // 1. الحلقة الأساسية إذا كانت في نفس المسجد
        if ($teacher->quranCircle && $teacher->quranCircle->mosque_id == $mosque->id) {
            $circles->push($teacher->quranCircle);
        }

        // 2. الحلقات من التكليفات الإضافية في هذا المسجد
        $additionalCircles = \App\Models\TeacherCircleAssignment::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereHas('circle', function($q) use ($mosque) {
                $q->where('mosque_id', $mosque->id);
            })
            ->with(['circle'])
            ->get();

        foreach ($additionalCircles as $assign) {
            if ($assign->circle && !$circles->contains('id', $assign->circle->id)) {
                $circles->push($assign->circle);
            }
        }

        if ($circles->isEmpty()) {
            return null;
        }

        // جمع الطلاب من جميع الحلقات في هذا المسجد
        $studentsData = collect();
        
        foreach ($circles as $circle) {            $students = Student::where('quran_circle_id', $circle->id)
                ->with([
                    'quranCircle:id,name,circle_type',
                    'circleGroup:id,name'
                ])
                ->get();

            foreach ($students as $student) {
                $studentsData->push([
                    'student_id' => $student->id,
                    'اسم_الطالب' => $student->name,
                    'رقم_الهوية' => $student->identity_number,
                    'رقم_الهاتف' => $student->phone,
                    'العمر' => $student->birth_date ? $student->birth_date->age : null,
                    'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
                    'حي_السكن' => $student->neighborhood,
                    'نشط' => $student->is_active ? 'نعم' : 'لا',
                    'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
                    'عدد_الغياب' => $student->absence_count ?? 0,
                    'عدد_الأجزاء' => $student->parts_count ?? 0,
                    'ملاحظات_المعلم' => $student->teacher_notes,
                    
                    // تفاصيل الحلقة
                    'حلقة' => [
                        'id' => $circle->id,
                        'اسم_الحلقة' => $circle->name,
                        'نوع_الحلقة' => $circle->circle_type,
                        'المستوى' => $circle->circle_type,
                        'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                    ]
                ]);
            }
        }        // بناء بيانات الحلقات
        $circlesData = $circles->map(function($circle) use ($studentsData) {
            $circleStudents = $studentsData->where('حلقة.id', $circle->id);
            
            return [
                'id' => $circle->id,
                'اسم_الحلقة' => $circle->name,
                'نوع_الحلقة' => $circle->circle_type,
                'المستوى' => $circle->circle_type,
                'عدد_الطلاب' => $circleStudents->count(),
                'الطلاب_النشطون' => $circleStudents->where('نشط', 'نعم')->count()
            ];
        })->toArray();

        return [
            'معرف_المسجد' => $mosque->id,
            'اسم_المسجد' => $mosque->name,
            'الحي' => $mosque->neighborhood,
            'الشارع' => $mosque->street,
            'نوع_التكليف' => $assignmentType,
            'تاريخ_بداية_التكليف' => $assignment ? $assignment->start_date?->format('Y-m-d') : null,
            'تاريخ_نهاية_التكليف' => $assignment ? $assignment->end_date?->format('Y-m-d') : null,
            
            'إحصائيات' => [
                'عدد_الحلقات' => $circles->count(),
                'عدد_الطلاب' => $studentsData->count(),
                'الطلاب_النشطون' => $studentsData->where('نشط', 'نعم')->count()
            ],
            'الحلقات' => $circlesData,
            'الطلاب' => $studentsData->values()->toArray()
        ];
    }

    /**
     * الحصول على جميع الطلاب المرتبطين بالمعلم مع تفاصيل الحلقة والمسجد
     */
    public function getTeacherStudents($id): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'mosque:id,name,neighborhood,street',
                'quranCircle'
            ])->findOrFail($id);

            $allStudents = collect();            // 1. جلب الطلاب من الحلقة الأساسية للمعلم - فقط الذين مسندين للمعلم فعلياً
            if ($teacher->quranCircle) {                
                // أ. طلاب الحلقات الفرعية المسندة لهذا المعلم
                $mainCircleStudents = Student::whereHas('circleGroup', function($query) use ($teacher) {
                    $query->where('quran_circle_id', $teacher->quranCircle->id)
                          ->where('teacher_id', $teacher->id);
                })
                    ->with([
                        'quranCircle:id,name,circle_type,circle_type,mosque_id',
                        'quranCircle.mosque:id,name,neighborhood,street',
                        'circleGroup:id,name',
                        'curricula'
                    ])
                    ->get();

                foreach ($mainCircleStudents as $student) {
                    $allStudents->push([
                        'student_id' => $student->id,
                        'اسم_الطالب' => $student->name,
                        'رقم_الهوية' => $student->identity_number,
                        'رقم_الهاتف' => $student->phone,
                        'العمر' => $student->birth_date ? $student->birth_date->age : null,
                        'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
                        'حي_السكن' => $student->neighborhood,
                        'نشط' => $student->is_active ? 'نعم' : 'لا',
                        'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
                        'عدد_الغياب' => $student->absence_count ?? 0,
                        'عدد_الأجزاء' => $student->parts_count ?? 0,
                        'ملاحظات_المعلم' => $student->teacher_notes,
                        
                        // تفاصيل الحلقة
                        'حلقة' => [
                            'id' => $student->quranCircle->id,
                            'اسم_الحلقة' => $student->quranCircle->name,
                            'نوع_الحلقة' => $student->quranCircle->circle_type,
                            'المستوى' => $student->quranCircle->circle_type,
                            'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                            'نوع_التكليف' => 'أساسي'
                        ],
                          // تفاصيل المسجد
                        'مسجد' => [
                            'id' => $student->quranCircle->mosque->id ?? $teacher->mosque->id,
                            'اسم_المسجد' => $student->quranCircle->mosque->name ?? $teacher->mosque->name,
                            'الحي' => $student->quranCircle->mosque->neighborhood ?? $teacher->mosque->neighborhood,
                            'الشارع' => $student->quranCircle->mosque->street ?? $teacher->mosque->street,
                        ],
                        
                        // المنهج الحالي
                        'منهج_حالي' => [
                            'السورة_الحالية' => 'غير محدد',
                            'الآية_الحالية' => 0,
                            'عدد_الصفحات_المحفوظة' => 0,
                            'عدد_المناهج_النشطة' => $student->curricula->where('status', 'قيد التنفيذ')->count(),                        ],
                        
                        // إحصائيات الحضور للشهر الحالي
                        'حضور_الشهر_الحالي' => [
                            'عدد_أيام_الحضور' => 0, // سيتم تحسين هذا لاحقاً
                            'عدد_أيام_الغياب' => 0, // سيتم تحسين هذا لاحقاً
                            'نسبة_الحضور' => 0 // سيتم تحسين هذا لاحقاً
                        ]
                    ]);
                }

                // ب. طلاب الحلقات الفرعية التي يشرف عليها المعلم
                $circleGroups = \App\Models\CircleGroup::where('quran_circle_id', $teacher->quranCircle->id)
                    ->where('teacher_id', $teacher->id)
                    ->get();

                foreach ($circleGroups as $circleGroup) {
                    $circleGroupStudents = Student::where('circle_group_id', $circleGroup->id)
                        ->with([
                            'quranCircle:id,name,circle_type,mosque_id',
                            'quranCircle.mosque:id,name,neighborhood,street',
                            'circleGroup:id,name',
                            'curricula'
                        ])
                        ->get();

                    foreach ($circleGroupStudents as $student) {
                        // تحقق من عدم تكرار الطالب
                        $exists = $allStudents->contains(function($existingStudent) use ($student) {
                            return $existingStudent['student_id'] == $student->id;
                        });

                        if (!$exists) {
                            $allStudents->push([
                                'student_id' => $student->id,
                                'اسم_الطالب' => $student->name,
                                'رقم_الهوية' => $student->identity_number,
                                'رقم_الهاتف' => $student->phone,
                                'العمر' => $student->birth_date ? $student->birth_date->age : null,
                                'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
                                'حي_السكن' => $student->neighborhood,
                                'نشط' => $student->is_active ? 'نعم' : 'لا',
                                'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
                                'عدد_الغياب' => $student->absence_count ?? 0,
                                'عدد_الأجزاء' => $student->parts_count ?? 0,
                                'ملاحظات_المعلم' => $student->teacher_notes,
                                
                                // تفاصيل الحلقة
                                'حلقة' => [
                                    'id' => $student->quranCircle->id,
                                    'اسم_الحلقة' => $student->quranCircle->name,
                                    'نوع_الحلقة' => $student->quranCircle->circle_type,
                                    'المستوى' => $student->quranCircle->circle_type,
                                    'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                                    'نوع_التكليف' => 'حلقة فرعية'
                                ],
                                  // تفاصيل المسجد
                                'مسجد' => [
                                    'id' => $student->quranCircle->mosque->id ?? $teacher->mosque->id,
                                    'اسم_المسجد' => $student->quranCircle->mosque->name ?? $teacher->mosque->name,
                                    'الحي' => $student->quranCircle->mosque->neighborhood ?? $teacher->mosque->neighborhood,
                                    'الشارع' => $student->quranCircle->mosque->street ?? $teacher->mosque->street,
                                ],
                                
                                // المنهج الحالي
                                'منهج_حالي' => [
                                    'السورة_الحالية' => 'غير محدد',
                                    'الآية_الحالية' => 0,
                                    'عدد_الصفحات_المحفوظة' => 0,
                                    'عدد_المناهج_النشطة' => $student->curricula->where('status', 'قيد التنفيذ')->count(),
                                ],
                                
                                // إحصائيات الحضور للشهر الحالي
                                'حضور_الشهر_الحالي' => [
                                    'عدد_أيام_الحضور' => 0,
                                    'عدد_أيام_الغياب' => 0,
                                    'نسبة_الحضور' => 0
                                ]
                            ]);
                        }
                    }
                }
            }

            // 2. جلب الطلاب من التكليفات الإضافية للمعلم
            $activeAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $id)
                ->where('is_active', true)
                ->with(['circle.students', 'circle.mosque'])
                ->get();

            foreach ($activeAssignments as $assignment) {
                $circle = $assignment->circle;
                  // تجنب تكرار الطلاب إذا كانوا موجودين أصلاً في الحلقة الأساسية
                // جلب فقط الطلاب المسندين للمعلم في هذه الحلقة
                $assignmentStudents = Student::whereHas('circleGroup', function($query) use ($circle, $id) {
                        $query->where('quran_circle_id', $circle->id)
                              ->where('teacher_id', $id);
                    })
                    ->with([
                        'quranCircle:id,name,circle_type,circle_type,mosque_id',
                        'quranCircle.mosque:id,name,neighborhood,street',
                        'circleGroup:id,name',
                        'curricula',
                        'attendances' => function($q) {
                            $q->whereMonth('date', now()->month)
                              ->whereYear('date', now()->year);
                        }
                    ])
                    ->get();

                foreach ($assignmentStudents as $student) {
                    // تحقق من عدم تكرار الطالب
                    $exists = $allStudents->contains(function($existingStudent) use ($student) {
                        return $existingStudent['student_id'] == $student->id;
                    });

                    if (!$exists) {
                        $allStudents->push([
                            'student_id' => $student->id,
                            'اسم_الطالب' => $student->name,
                            'رقم_الهوية' => $student->identity_number,
                            'رقم_الهاتف' => $student->phone,
                            'العمر' => $student->birth_date ? $student->birth_date->age : null,
                            'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
                            'حي_السكن' => $student->neighborhood,
                            'نشط' => $student->is_active ? 'نعم' : 'لا',
                            'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
                            'عدد_الغياب' => $student->absence_count ?? 0,
                            'عدد_الأجزاء' => $student->parts_count ?? 0,
                            'ملاحظات_المعلم' => $student->teacher_notes,
                            
                            // تفاصيل الحلقة
                            'حلقة' => [
                                'id' => $student->quranCircle->id,
                                'اسم_الحلقة' => $student->quranCircle->name,
                                'نوع_الحلقة' => $student->quranCircle->circle_type,
                                'المستوى' => $student->quranCircle->circle_type,
                                'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                                'نوع_التكليف' => 'تكليف إضافي',
                                'تاريخ_بداية_التكليف' => $assignment->start_date?->format('Y-m-d'),
                                'تاريخ_نهاية_التكليف' => $assignment->end_date?->format('Y-m-d'),
                            ],
                            
                            // تفاصيل المسجد
                            'مسجد' => [
                                'id' => $circle->mosque->id,
                                'اسم_المسجد' => $circle->mosque->name,
                                'الحي' => $circle->mosque->neighborhood,
                                'الشارع' => $circle->mosque->street,
                            ],
                              // المنهج الحالي
                            'منهج_حالي' => [
                                'السورة_الحالية' => 'غير محدد',
                                'الآية_الحالية' => 0,
                                'عدد_الصفحات_المحفوظة' => 0,
                                'عدد_المناهج_النشطة' => $student->curricula->where('status', 'قيد التنفيذ')->count(),
                            ],
                            
                            // إحصائيات الحضور للشهر الحالي
                            'حضور_الشهر_الحالي' => [
                                'عدد_أيام_الحضور' => $student->attendances->where('status', 'حاضر')->count(),
                                'عدد_أيام_الغياب' => $student->attendances->where('status', 'غائب')->count(),
                                'نسبة_الحضور' => $student->attendances->count() > 0 ? 
                                    round(($student->attendances->where('status', 'حاضر')->count() / $student->attendances->count()) * 100, 1) : 0                            ]
                        ]);
                    }
                }
                
                // 2ب. جلب الطلاب من الحلقات الفرعية في التكليفات الإضافية
                $assignmentCircleGroups = \App\Models\CircleGroup::where('quran_circle_id', $circle->id)
                    ->where('teacher_id', $id)
                    ->get();

                foreach ($assignmentCircleGroups as $circleGroup) {
                    $groupStudents = Student::where('circle_group_id', $circleGroup->id)
                        ->with([
                            'quranCircle:id,name,circle_type,mosque_id',
                            'quranCircle.mosque:id,name,neighborhood,street',
                            'circleGroup:id,name',
                            'curricula',
                            'attendances' => function($q) {
                                $q->whereMonth('date', now()->month)
                                  ->whereYear('date', now()->year);
                            }
                        ])
                        ->get();

                    foreach ($groupStudents as $student) {
                        // تحقق من عدم تكرار الطالب
                        $exists = $allStudents->contains(function($existingStudent) use ($student) {
                            return $existingStudent['student_id'] == $student->id;
                        });

                        if (!$exists) {
                            $allStudents->push([
                                'student_id' => $student->id,
                                'اسم_الطالب' => $student->name,
                                'رقم_الهوية' => $student->identity_number,
                                'رقم_الهاتف' => $student->phone,
                                'العمر' => $student->birth_date ? $student->birth_date->age : null,
                                'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
                                'حي_السكن' => $student->neighborhood,
                                'نشط' => $student->is_active ? 'نعم' : 'لا',
                                'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
                                'عدد_الغياب' => $student->absence_count ?? 0,
                                'عدد_الأجزاء' => $student->parts_count ?? 0,
                                'ملاحظات_المعلم' => $student->teacher_notes,
                                
                                // تفاصيل الحلقة
                                'حلقة' => [
                                    'id' => $student->quranCircle->id,
                                    'اسم_الحلقة' => $student->quranCircle->name,
                                    'نوع_الحلقة' => $student->quranCircle->circle_type,
                                    'المستوى' => $student->quranCircle->circle_type,
                                    'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                                    'نوع_التكليف' => 'تكليف إضافي - حلقة فرعية',
                                    'تاريخ_بداية_التكليف' => $assignment->start_date?->format('Y-m-d'),
                                    'تاريخ_نهاية_التكليف' => $assignment->end_date?->format('Y-m-d'),
                                ],
                                
                                // تفاصيل المسجد
                                'مسجد' => [
                                    'id' => $circle->mosque->id,
                                    'اسم_المسجد' => $circle->mosque->name,
                                    'الحي' => $circle->mosque->neighborhood,
                                    'الشارع' => $circle->mosque->street,
                                ],
                                
                                // المنهج الحالي
                                'منهج_حالي' => [
                                    'السورة_الحالية' => 'غير محدد',
                                    'الآية_الحالية' => 0,
                                    'عدد_الصفحات_المحفوظة' => 0,
                                    'عدد_المناهج_النشطة' => $student->curricula->where('status', 'قيد التنفيذ')->count(),
                                ],
                                
                                // إحصائيات الحضور للشهر الحالي
                                'حضور_الشهر_الحالي' => [
                                    'عدد_أيام_الحضور' => $student->attendances->where('status', 'حاضر')->count(),
                                    'عدد_أيام_الغياب' => $student->attendances->where('status', 'غائب')->count(),
                                    'نسبة_الحضور' => $student->attendances->count() > 0 ? 
                                        round(($student->attendances->where('status', 'حاضر')->count() / $student->attendances->count()) * 100, 1) : 0
                                ]
                            ]);
                        }
                    }
                }
            }

            // إحصائيات عامة
            $totalStudents = $allStudents->count();
            $activeStudents = $allStudents->where('نشط', 'نعم')->count();
            $totalMosques = $allStudents->unique('مسجد.id')->count();
            $totalCircles = $allStudents->unique('حلقة.id')->count();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب طلاب المعلم بنجاح',
                'بيانات_المعلم' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الهاتف' => $teacher->phone,
                ],
                'إحصائيات_عامة' => [
                    'إجمالي_الطلاب' => $totalStudents,
                    'الطلاب_النشطون' => $activeStudents,
                    'الطلاب_غير_النشطين' => $totalStudents - $activeStudents,
                    'عدد_المساجد' => $totalMosques,
                    'عدد_الحلقات' => $totalCircles,
                    'متوسط_نسبة_الحضور' => round($allStudents->avg('حضور_الشهر_الحالي.نسبة_الحضور'), 1),
                    'إجمالي_الصفحات_المحفوظة' => $allStudents->sum('منهج_حالي.عدد_الصفحات_المحفوظة'),
                    'تاريخ_التحديث' => now()->format('Y-m-d H:i:s')
                ],
                'الطلاب' => $allStudents->values()->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'المعلم غير موجود',
                'error' => 'teacher_not_found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب طلاب المعلم',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على طلاب معلم محدد من مسجد محدد
     */
    public function getTeacherStudentsFromMosque($teacherId, $mosqueId): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'mosque:id,name,neighborhood,street',
                'quranCircle'
            ])->findOrFail($teacherId);

            $mosque = \App\Models\Mosque::findOrFail($mosqueId);

            // التحقق من أن المعلم يعمل في هذا المسجد
            $teacherWorksInMosque = false;
            $allStudents = collect();

            // 1. التحقق من المسجد الأساسي للمعلم
            if ($teacher->mosque && $teacher->mosque->id == $mosque->id) {
                $teacherWorksInMosque = true;
                  // جلب طلاب الحلقة الأساسية في هذا المسجد
                if ($teacher->quranCircle && $teacher->quranCircle->mosque_id == $mosque->id) {
                    // 1. طلاب الحلقة الرئيسية مباشرة
                    $mainCircleStudents = Student::where('quran_circle_id', $teacher->quranCircle->id)
                        ->with([
                            'quranCircle:id,name,circle_type,mosque_id',
                            'quranCircle.mosque:id,name,neighborhood,street',
                            'circleGroup:id,name',
                            'curricula'
                        ])
                        ->get();

                    foreach ($mainCircleStudents as $student) {
                        $allStudents->push($this->formatStudentData($student, 'أساسي', $teacher, $mosque));
                    }                    // 2. طلاب الحلقات الفرعية إذا كان المعلم مسؤولاً عنها
                    $circleGroups = \App\Models\CircleGroup::where('quran_circle_id', $teacher->quranCircle->id)
                        ->where('teacher_id', $teacher->id)
                        ->get();

                    foreach ($circleGroups as $circleGroup) {
                        $circleGroupStudents = Student::where('circle_group_id', $circleGroup->id)
                            ->with([
                                'quranCircle:id,name,circle_type,mosque_id',
                                'quranCircle.mosque:id,name,neighborhood,street',
                                'circleGroup:id,name',
                                'curricula'
                            ])
                            ->get();

                        foreach ($circleGroupStudents as $student) {
                            // تحقق من عدم تكرار الطالب
                            $exists = $allStudents->contains(function($existingStudent) use ($student) {
                                return $existingStudent['student_id'] == $student->id;
                            });

                            if (!$exists) {
                                $allStudents->push($this->formatStudentData($student, 'حلقة فرعية', $teacher, $mosque));
                            }
                        }
                    }
                }
            }

            // 2. التحقق من التكليفات الإضافية في هذا المسجد
            $activeAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->whereHas('circle', function($q) use ($mosqueId) {
                    $q->where('mosque_id', $mosqueId);
                })
                ->with(['circle.students', 'circle.mosque'])
                ->get();

            if ($activeAssignments->count() > 0) {
                $teacherWorksInMosque = true;                foreach ($activeAssignments as $assignment) {
                    $circle = $assignment->circle;
                    
                    // جلب طلاب الحلقات الفرعية المسندة للمعلم فقط
                    $circleGroups = \App\Models\CircleGroup::where('quran_circle_id', $circle->id)
                        ->where('teacher_id', $teacher->id)
                        ->get();

                    foreach ($circleGroups as $circleGroup) {
                        $circleGroupStudents = Student::where('circle_group_id', $circleGroup->id)
                            ->with([
                                'quranCircle:id,name,circle_type,mosque_id',
                                'quranCircle.mosque:id,name,neighborhood,street',
                                'circleGroup:id,name',
                                'curricula'
                            ])
                            ->get();

                        foreach ($circleGroupStudents as $student) {
                            // تحقق من عدم تكرار الطالب
                            $exists = $allStudents->contains(function($existingStudent) use ($student) {
                                return $existingStudent['student_id'] == $student->id;
                            });                            if (!$exists) {
                                $allStudents->push($this->formatStudentData($student, 'تكليف إضافي', $teacher, $mosque, $assignment));
                            }
                        }
                    }
                }
            }

            // التحقق من أن المعلم يعمل في هذا المسجد
            if (!$teacherWorksInMosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم لا يعمل في هذا المسجد'
                ], 404);
            }

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب طلاب المعلم من المسجد المحدد بنجاح',
                'بيانات_المعلم' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الهاتف' => $teacher->phone
                ],
                'بيانات_المسجد' => [
                    'id' => $mosque->id,
                    'الاسم' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'العنوان' => $mosque->address
                ],
                'إحصائيات' => [
                    'إجمالي_الطلاب' => $allStudents->count(),
                    'الطلاب_النشطون' => $allStudents->where('نشط', 'نعم')->count(),
                    'عدد_الحلقات' => $allStudents->groupBy('حلقة.id')->count(),
                    'تاريخ_التحديث' => now()->format('Y-m-d H:i:s')
                ],
                'الطلاب' => $allStudents->values()->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'حدث خطأ أثناء جلب طلاب المعلم من المسجد',
                'خطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تنسيق بيانات الطالب للاستجابة
     */
    private function formatStudentData($student, $assignmentType, $teacher, $mosque, $assignment = null): array
    {
        return [
            'student_id' => $student->id,
            'اسم_الطالب' => $student->name,
            'رقم_الهوية' => $student->identity_number,
            'رقم_الهاتف' => $student->phone,
            'العمر' => $student->birth_date ? $student->birth_date->age : null,
            'الجنس' => $student->gender == 'male' ? 'ذكر' : 'أنثى',
            'حي_السكن' => $student->neighborhood,
            'نشط' => $student->is_active ? 'نعم' : 'لا',
            'تاريخ_التسجيل' => $student->enrollment_date?->format('Y-m-d'),
            'عدد_الغياب' => $student->absence_count ?? 0,
            'عدد_الأجزاء' => $student->parts_count ?? 0,
            'ملاحظات_المعلم' => $student->teacher_notes,
            
            // تفاصيل الحلقة
            'حلقة' => [
                'id' => $student->quranCircle->id,
                'اسم_الحلقة' => $student->quranCircle->name,
                'نوع_الحلقة' => $student->quranCircle->circle_type,
                'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                'نوع_التكليف' => $assignmentType,
                'تاريخ_بداية_التكليف' => $assignment?->start_date?->format('Y-m-d'),
                'تاريخ_نهاية_التكليف' => $assignment?->end_date?->format('Y-m-d'),
            ],
            
            // تفاصيل المسجد (مؤكدة)
            'مسجد' => [
                'id' => $mosque->id,
                'اسم_المسجد' => $mosque->name,
                'الحي' => $mosque->neighborhood,
                'الشارع' => $mosque->street,
                'العنوان' => $mosque->address
            ],
            
            // المنهج الحالي
            'منهج_حالي' => [
                'السورة_الحالية' => 'غير محدد',
                'الآية_الحالية' => 0,
                'عدد_الصفحات_المحفوظة' => 0,
                'عدد_المناهج_النشطة' => $student->curricula->where('status', 'قيد التنفيذ')->count(),
            ],
            
            // إحصائيات الحضور للشهر الحالي
            'حضور_الشهر_الحالي' => [
                'عدد_أيام_الحضور' => 0,
                'عدد_أيام_الغياب' => 0,
                'نسبة_الحضور' => 0
            ]
        ];
    }
}
