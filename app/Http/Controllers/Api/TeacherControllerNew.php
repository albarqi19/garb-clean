<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherControllerNew extends Controller
{
    /**
     * قائمة المعلمين
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $teachers = Teacher::with(['mosque:id,name,neighborhood', 'quranCircle:id,name'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('identity_number', 'like', '%' . $request->search . '%');
                })
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'تم جلب قائمة المعلمين بنجاح',
                'data' => $teachers
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب قائمة المعلمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تفاصيل معلم محدد
     */
    public function show($id): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'mosque:id,name,neighborhood,street',
                'quranCircle:id,name,grade_level,circle_type'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات المعلم بنجاح',
                'data' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الهاتف' => $teacher->phone,
                    'الجنسية' => $teacher->nationality,
                    'المسمى_الوظيفي' => $teacher->job_title,
                    'نوع_المهمة' => $teacher->task_type,
                    'المسجد' => $teacher->mosque ? [
                        'id' => $teacher->mosque->id,
                        'الاسم' => $teacher->mosque->name,
                        'الحي' => $teacher->mosque->neighborhood,
                        'الشارع' => $teacher->mosque->street
                    ] : null,
                    'الحلقة' => $teacher->quranCircle ? [
                        'id' => $teacher->quranCircle->id,
                        'الاسم' => $teacher->quranCircle->name,
                        'المستوى' => $teacher->quranCircle->grade_level,
                        'النوع' => $teacher->quranCircle->circle_type
                    ] : null
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حلقات معلم محدد
     */
    public function teacherCircles($id): JsonResponse
    {
        try {
            $teacher = Teacher::with([
                'quranCircle:id,name,grade_level,circle_type,mosque_id',
                'quranCircle.mosque:id,name,neighborhood'
            ])->findOrFail($id);

            $circles = collect();

            // الحلقة الأساسية
            if ($teacher->quranCircle) {
                $circles->push([
                    'id' => $teacher->quranCircle->id,
                    'اسم_الحلقة' => $teacher->quranCircle->name,
                    'المستوى' => $teacher->quranCircle->grade_level,
                    'النوع' => $teacher->quranCircle->circle_type,
                    'نوع_التكليف' => 'أساسي',
                    'المسجد' => $teacher->quranCircle->mosque ? [
                        'id' => $teacher->quranCircle->mosque->id,
                        'الاسم' => $teacher->quranCircle->mosque->name,
                        'الحي' => $teacher->quranCircle->mosque->neighborhood
                    ] : null
                ]);
            }

            // التكليفات الإضافية
            $additionalAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $id)
                ->where('is_active', true)
                ->with(['circle:id,name,grade_level,circle_type,mosque_id', 'circle.mosque:id,name,neighborhood'])
                ->get();

            foreach ($additionalAssignments as $assignment) {
                if ($assignment->circle) {
                    $circles->push([
                        'id' => $assignment->circle->id,
                        'اسم_الحلقة' => $assignment->circle->name,
                        'المستوى' => $assignment->circle->grade_level,
                        'النوع' => $assignment->circle->circle_type,
                        'نوع_التكليف' => 'إضافي',
                        'تاريخ_البداية' => $assignment->start_date?->format('Y-m-d'),
                        'تاريخ_النهاية' => $assignment->end_date?->format('Y-m-d'),
                        'المسجد' => $assignment->circle->mosque ? [
                            'id' => $assignment->circle->mosque->id,
                            'الاسم' => $assignment->circle->mosque->name,
                            'الحي' => $assignment->circle->mosque->neighborhood
                        ] : null
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب حلقات المعلم بنجاح',
                'teacher_name' => $teacher->name,
                'total_circles' => $circles->count(),
                'circles' => $circles->values()->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب حلقات المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * طلاب معلم محدد
     */
    public function getStudents($id): JsonResponse
    {
        try {
            $teacher = Teacher::findOrFail($id);
            $studentsData = collect();

            // 1. طلاب الحلقة الأساسية
            if ($teacher->quranCircle) {
                $primaryStudents = Student::where('quran_circle_id', $teacher->quranCircle->id)
                    ->with(['quranCircle:id,name', 'circleGroup:id,name'])
                    ->get();

                foreach ($primaryStudents as $student) {
                    $studentsData->push([
                        'student_id' => $student->id,
                        'اسم_الطالب' => $student->name,
                        'رقم_الهوية' => $student->identity_number,
                        'رقم_الهاتف' => $student->phone,
                        'نشط' => $student->is_active ? 'نعم' : 'لا',
                        'نوع_الانتساب' => 'حلقة أساسية',
                        'حلقة' => [
                            'id' => $teacher->quranCircle->id,
                            'الاسم' => $teacher->quranCircle->name
                        ],
                        'المجموعة' => $student->circleGroup ? $student->circleGroup->name : null
                    ]);
                }
            }

            // 2. طلاب التكليفات الإضافية
            $additionalAssignments = \App\Models\TeacherCircleAssignment::where('teacher_id', $id)
                ->where('is_active', true)
                ->with(['circle'])
                ->get();

            foreach ($additionalAssignments as $assignment) {
                if ($assignment->circle) {
                    $additionalStudents = Student::where('quran_circle_id', $assignment->circle->id)
                        ->with(['quranCircle:id,name', 'circleGroup:id,name'])
                        ->get();

                    foreach ($additionalStudents as $student) {
                        // تجنب التكرار
                        if (!$studentsData->contains('student_id', $student->id)) {
                            $studentsData->push([
                                'student_id' => $student->id,
                                'اسم_الطالب' => $student->name,
                                'رقم_الهوية' => $student->identity_number,
                                'رقم_الهاتف' => $student->phone,
                                'نشط' => $student->is_active ? 'نعم' : 'لا',
                                'نوع_الانتساب' => 'تكليف إضافي',
                                'حلقة' => [
                                    'id' => $assignment->circle->id,
                                    'الاسم' => $assignment->circle->name
                                ],
                                'المجموعة' => $student->circleGroup ? $student->circleGroup->name : null
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب طلاب المعلم بنجاح',
                'teacher_name' => $teacher->name,
                'total_students' => $studentsData->count(),
                'active_students' => $studentsData->where('نشط', 'نعم')->count(),
                'students' => $studentsData->values()->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب طلاب المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * المساجد التي يعمل بها المعلم مع طلاب كل مسجد
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
        
        foreach ($circles as $circle) {
            $students = Student::where('quran_circle_id', $circle->id)
                ->with([
                    'quranCircle:id,name,grade_level,circle_type',
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
                        'المستوى' => $circle->grade_level,
                        'الحلقة_الفرعية' => $student->circleGroup ? $student->circleGroup->name : null,
                    ]
                ]);
            }
        }

        // بناء بيانات الحلقات
        $circlesData = $circles->map(function($circle) use ($studentsData) {
            $circleStudents = $studentsData->where('حلقة.id', $circle->id);
            
            return [
                'id' => $circle->id,
                'اسم_الحلقة' => $circle->name,
                'نوع_الحلقة' => $circle->circle_type,
                'المستوى' => $circle->grade_level,
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
}
