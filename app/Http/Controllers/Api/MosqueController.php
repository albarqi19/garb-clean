<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mosque;
use App\Models\QuranCircle;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * API للمساجد وعلاقاتها مع الحلقات والمعلمين والطلاب
 */
class MosqueController extends Controller
{
    /**
     * قائمة المساجد مع الفلترة والبحث
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Mosque::with(['quranCircles']);

            // البحث في اسم المسجد أو الحي
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('neighborhood', 'like', "%{$search}%")
                      ->orWhere('street', 'like', "%{$search}%");
                });
            }

            // فلترة حسب الحي
            if ($request->filled('neighborhood')) {
                $query->where('neighborhood', $request->neighborhood);
            }

            // فلترة المساجد التي بها حلقات نشطة
            if ($request->filled('has_active_circles') && $request->has_active_circles) {
                $query->whereHas('quranCircles', function ($q) {
                    $q->where('circle_status', 'نشطة');
                });
            }

            // الترتيب
            $orderBy = $request->get('order_by', 'name');
            $orderDirection = $request->get('order_direction', 'asc');
            $query->orderBy($orderBy, $orderDirection);

            // التصفح
            $perPage = min($request->get('per_page', 15), 100);
            $mosques = $query->paginate($perPage);

            $data = $mosques->through(function ($mosque) {
                return [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'رقم_الاتصال' => $mosque->contact_number,
                    'الإحداثيات' => [
                        'خط_العرض' => $mosque->location_lat,
                        'خط_الطول' => $mosque->location_long,
                        'رابط_الخريطة' => $mosque->google_maps_url,
                    ],
                    'عدد_الحلقات' => $mosque->quranCircles->count(),
                    'الحلقات_النشطة' => $mosque->quranCircles->where('circle_status', 'نشطة')->count(),
                    'تاريخ_الإنشاء' => $mosque->created_at->format('Y-m-d H:i:s'),
                    'تاريخ_التحديث' => $mosque->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب قائمة المساجد بنجاح',
                'البيانات' => $data,
                'معلومات_التصفح' => [
                    'الصفحة_الحالية' => $mosques->currentPage(),
                    'العناصر_في_الصفحة' => $mosques->count(),
                    'إجمالي_العناصر' => $mosques->total(),
                    'إجمالي_الصفحات' => $mosques->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب قائمة المساجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تفاصيل مسجد محدد
     */
    public function show($id): JsonResponse
    {
        try {
            $mosque = Mosque::with([
                'quranCircles.teachers',
                'quranCircles.students',
                'tasks' => function ($query) {
                    $query->latest()->take(5);
                }
            ])->find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            // حساب إحصائيات المسجد
            $totalTeachers = Teacher::where('mosque_id', $mosque->id)->count();
            $totalStudents = Student::where('mosque_id', $mosque->id)->count();
            
            $data = [
                'id' => $mosque->id,
                'المعلومات_الأساسية' => [
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'رقم_الاتصال' => $mosque->contact_number,
                ],
                'معلومات_الموقع' => [
                    'خط_العرض' => $mosque->location_lat,
                    'خط_الطول' => $mosque->location_long,
                    'رابط_الخريطة' => $mosque->google_maps_url,
                ],
                'الإحصائيات' => [
                    'عدد_الحلقات' => $mosque->quranCircles->count(),
                    'الحلقات_النشطة' => $mosque->quranCircles->where('circle_status', 'نشطة')->count(),
                    'الحلقات_المعلقة' => $mosque->quranCircles->where('circle_status', 'معلقة')->count(),
                    'عدد_المعلمين' => $totalTeachers,
                    'عدد_الطلاب' => $totalStudents,
                    'المهام_النشطة' => $mosque->activeTasks()->count(),
                ],
                'الحلقات' => $mosque->quranCircles->map(function ($circle) {
                    return [
                        'id' => $circle->id,
                        'اسم_الحلقة' => $circle->name,
                        'نوع_الحلقة' => $circle->circle_type,
                        'حالة_الحلقة' => $circle->circle_status,
                        'الفترة_الزمنية' => $circle->time_period,
                        'عدد_المعلمين' => $circle->teachers->count(),
                        'عدد_الطلاب' => $circle->students->count(),
                        'لديه_رتل' => $circle->has_ratel,
                        'لديه_قياس' => $circle->has_qias,
                    ];
                }),
                'المهام_الأخيرة' => $mosque->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'العنوان' => $task->title,
                        'الحالة' => $task->status,
                        'الأولوية' => $task->priority,
                        'تاريخ_الاستحقاق' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    ];
                }),
                'تاريخ_الإنشاء' => $mosque->created_at->format('Y-m-d H:i:s'),
                'تاريخ_التحديث' => $mosque->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب تفاصيل المسجد بنجاح',
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب تفاصيل المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء مسجد جديد
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'neighborhood' => 'required|string|max:255',
                'street' => 'nullable|string|max:255',
                'location_lat' => 'nullable|numeric|between:-90,90',
                'location_long' => 'nullable|numeric|between:-180,180',
                'contact_number' => 'nullable|string|max:20',
            ], [
                'name.required' => 'اسم المسجد مطلوب',
                'name.max' => 'اسم المسجد يجب ألا يتجاوز 255 حرف',
                'neighborhood.required' => 'الحي مطلوب',
                'neighborhood.max' => 'الحي يجب ألا يتجاوز 255 حرف',
                'location_lat.between' => 'خط العرض يجب أن يكون بين -90 و 90',
                'location_long.between' => 'خط الطول يجب أن يكون بين -180 و 180',
                'contact_number.max' => 'رقم الاتصال يجب ألا يتجاوز 20 رقم',
            ]);

            $mosque = Mosque::create($validatedData);

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم إنشاء المسجد بنجاح',
                'البيانات' => [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'رقم_الاتصال' => $mosque->contact_number,
                    'خط_العرض' => $mosque->location_lat,
                    'خط_الطول' => $mosque->location_long,
                    'رابط_الخريطة' => $mosque->google_maps_url,
                    'تاريخ_الإنشاء' => $mosque->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'بيانات غير صحيحة',
                'الأخطاء' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في إنشاء المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث مسجد موجود
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'neighborhood' => 'sometimes|required|string|max:255',
                'street' => 'nullable|string|max:255',
                'location_lat' => 'nullable|numeric|between:-90,90',
                'location_long' => 'nullable|numeric|between:-180,180',
                'contact_number' => 'nullable|string|max:20',
            ], [
                'name.required' => 'اسم المسجد مطلوب',
                'name.max' => 'اسم المسجد يجب ألا يتجاوز 255 حرف',
                'neighborhood.required' => 'الحي مطلوب',
                'neighborhood.max' => 'الحي يجب ألا يتجاوز 255 حرف',
                'location_lat.between' => 'خط العرض يجب أن يكون بين -90 و 90',
                'location_long.between' => 'خط الطول يجب أن يكون بين -180 و 180',
                'contact_number.max' => 'رقم الاتصال يجب ألا يتجاوز 20 رقم',
            ]);

            $mosque->update($validatedData);

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم تحديث المسجد بنجاح',
                'البيانات' => [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'رقم_الاتصال' => $mosque->contact_number,
                    'خط_العرض' => $mosque->location_lat,
                    'خط_الطول' => $mosque->location_long,
                    'رابط_الخريطة' => $mosque->google_maps_url,
                    'تاريخ_التحديث' => $mosque->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'بيانات غير صحيحة',
                'الأخطاء' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في تحديث المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف مسجد
     */
    public function destroy($id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            // التحقق من وجود حلقات مرتبطة
            if ($mosque->quranCircles()->count() > 0) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'لا يمكن حذف المسجد لوجود حلقات مرتبطة به'
                ], 400);
            }

            $mosque->delete();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم حذف المسجد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في حذف المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحلقات المرتبطة بمسجد محدد
     */
    public function circles($id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $circles = $mosque->quranCircles()->with(['teachers', 'students'])->get();

            $data = $circles->map(function ($circle) {
                return [
                    'id' => $circle->id,
                    'اسم_الحلقة' => $circle->name,
                    'نوع_الحلقة' => $circle->circle_type,
                    'حالة_الحلقة' => $circle->circle_status,
                    'الفترة_الزمنية' => $circle->time_period,
                    'رابط_التسجيل' => $circle->registration_link,
                    'لديه_رتل' => $circle->has_ratel,
                    'لديه_قياس' => $circle->has_qias,
                    'رابط_ماسر' => $circle->masser_link,
                    'عدد_المعلمين' => $circle->teachers->count(),
                    'عدد_الطلاب' => $circle->students->count(),
                    'المعلمين' => $circle->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'الاسم' => $teacher->name,
                            'رقم_الجوال' => $teacher->phone,
                            'الجنسية' => $teacher->nationality,
                            'نشط' => $teacher->is_active,
                        ];
                    }),
                    'تاريخ_الإنشاء' => $circle->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب حلقات المسجد بنجاح',
                'اسم_المسجد' => $mosque->name,
                'عدد_الحلقات' => $circles->count(),
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب حلقات المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * المعلمين المرتبطين بمسجد محدد
     */
    public function teachers($id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $teachers = Teacher::where('mosque_id', $mosque->id)
                              ->with(['quranCircle'])
                              ->get();

            $data = $teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الجوال' => $teacher->phone,
                    'الجنسية' => $teacher->nationality,
                    'المسمى_الوظيفي' => $teacher->job_title,
                    'نوع_المهمة' => $teacher->task_type,
                    'نوع_الحلقة' => $teacher->circle_type,
                    'وقت_العمل' => $teacher->work_time,
                    'نشط' => $teacher->is_active,
                    'راتل_مفعل' => $teacher->ratel_activated,
                    'الحلقة_المرتبطة' => $teacher->quranCircle ? [
                        'id' => $teacher->quranCircle->id,
                        'اسم_الحلقة' => $teacher->quranCircle->name,
                        'نوع_الحلقة' => $teacher->quranCircle->circle_type,
                    ] : null,
                    'تاريخ_البداية' => $teacher->start_date ? $teacher->start_date->format('Y-m-d') : null,
                    'تاريخ_الإنشاء' => $teacher->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب معلمي المسجد بنجاح',
                'اسم_المسجد' => $mosque->name,
                'عدد_المعلمين' => $teachers->count(),
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب معلمي المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الطلاب المرتبطين بمسجد محدد
     */
    public function students($id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $students = Student::where('mosque_id', $mosque->id)
                              ->with(['quranCircle', 'circleGroup'])
                              ->get();

            $data = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'الاسم' => $student->name,
                    'رقم_الهوية' => $student->identity_number,
                    'رقم_الجوال' => $student->phone,
                    'الجنسية' => $student->nationality,
                    'تاريخ_الميلاد' => $student->birth_date ? $student->birth_date->format('Y-m-d') : null,
                    'العمر' => $student->age,
                    'الحي' => $student->neighborhood,
                    'نشط' => $student->is_active_user,
                    'عدد_الغيابات' => $student->absence_count,
                    'عدد_الأجزاء' => $student->parts_count,
                    'آخر_اختبار' => $student->last_exam,
                    'الحلقة_المرتبطة' => $student->quranCircle ? [
                        'id' => $student->quranCircle->id,
                        'اسم_الحلقة' => $student->quranCircle->name,
                        'نوع_الحلقة' => $student->quranCircle->circle_type,
                    ] : null,
                    'الحلقة_الفرعية' => $student->circleGroup ? [
                        'id' => $student->circleGroup->id,
                        'اسم_المجموعة' => $student->circleGroup->name,
                    ] : null,
                    'تاريخ_التسجيل' => $student->enrollment_date ? $student->enrollment_date->format('Y-m-d') : null,
                    'تاريخ_الإنشاء' => $student->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب طلاب المسجد بنجاح',
                'اسم_المسجد' => $mosque->name,
                'عدد_الطلاب' => $students->count(),
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب طلاب المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات مسجد محدد
     */
    public function statistics($id): JsonResponse
    {
        try {
            $mosque = Mosque::with(['quranCircles'])->find($id);

            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            // حساب الإحصائيات
            $totalCircles = $mosque->quranCircles->count();
            $activeCircles = $mosque->quranCircles->where('circle_status', 'نشطة')->count();
            $suspendedCircles = $mosque->quranCircles->where('circle_status', 'معلقة')->count();
            $closedCircles = $mosque->quranCircles->where('circle_status', 'مغلقة')->count();

            $totalTeachers = Teacher::where('mosque_id', $mosque->id)->count();
            $activeTeachers = Teacher::where('mosque_id', $mosque->id)->where('is_active', true)->count();

            $totalStudents = Student::where('mosque_id', $mosque->id)->count();
            $activeStudents = Student::where('mosque_id', $mosque->id)->where('is_active_user', true)->count();

            // إحصائيات حسب نوع الحلقة
            $circlesByType = $mosque->quranCircles->groupBy('circle_type')->map->count();
            
            // إحصائيات حسب الفترة الزمنية
            $circlesByPeriod = $mosque->quranCircles->groupBy('time_period')->map->count();

            $data = [
                'معلومات_المسجد' => [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                ],
                'إحصائيات_الحلقات' => [
                    'إجمالي_الحلقات' => $totalCircles,
                    'الحلقات_النشطة' => $activeCircles,
                    'الحلقات_المعلقة' => $suspendedCircles,
                    'الحلقات_المغلقة' => $closedCircles,
                    'نسبة_الحلقات_النشطة' => $totalCircles > 0 ? round(($activeCircles / $totalCircles) * 100, 2) : 0,
                ],
                'إحصائيات_المعلمين' => [
                    'إجمالي_المعلمين' => $totalTeachers,
                    'المعلمين_النشطين' => $activeTeachers,
                    'المعلمين_غير_النشطين' => $totalTeachers - $activeTeachers,
                    'نسبة_المعلمين_النشطين' => $totalTeachers > 0 ? round(($activeTeachers / $totalTeachers) * 100, 2) : 0,
                ],
                'إحصائيات_الطلاب' => [
                    'إجمالي_الطلاب' => $totalStudents,
                    'الطلاب_النشطين' => $activeStudents,
                    'الطلاب_غير_النشطين' => $totalStudents - $activeStudents,
                    'نسبة_الطلاب_النشطين' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 2) : 0,
                    'متوسط_الطلاب_لكل_حلقة' => $totalCircles > 0 ? round($totalStudents / $totalCircles, 2) : 0,
                ],
                'توزيع_الحلقات_حسب_النوع' => $circlesByType,
                'توزيع_الحلقات_حسب_الفترة' => $circlesByPeriod,
                'المهام_النشطة' => $mosque->activeTasks()->count(),
                'المهام_المتأخرة' => $mosque->overdueTasks()->count(),
            ];

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم جلب إحصائيات المسجد بنجاح',
                'البيانات' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في جلب إحصائيات المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث عن المساجد القريبة من موقع معين
     */
    public function nearby(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:0.1|max:50', // نصف القطر بالكيلومتر
            ], [
                'latitude.required' => 'خط العرض مطلوب',
                'longitude.required' => 'خط الطول مطلوب',
                'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
                'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
                'radius.min' => 'نصف القطر يجب أن يكون على الأقل 0.1 كيلومتر',
                'radius.max' => 'نصف القطر يجب ألا يتجاوز 50 كيلومتر',
            ]);

            $latitude = $validatedData['latitude'];
            $longitude = $validatedData['longitude'];
            $radius = $validatedData['radius'] ?? 10; // افتراضي 10 كيلومتر

            // استعلام للبحث عن المساجد القريبة باستخدام صيغة Haversine
            $mosques = Mosque::selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(location_lat)) * cos(radians(location_long) - radians(?)) + sin(radians(?)) * sin(radians(location_lat)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('location_lat')
            ->whereNotNull('location_long')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->with(['quranCircles'])
            ->get();

            $data = $mosques->map(function ($mosque) {
                return [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood,
                    'الشارع' => $mosque->street,
                    'رقم_الاتصال' => $mosque->contact_number,
                    'الإحداثيات' => [
                        'خط_العرض' => $mosque->location_lat,
                        'خط_الطول' => $mosque->location_long,
                        'رابط_الخريطة' => $mosque->google_maps_url,
                    ],
                    'المسافة_كيلومتر' => round($mosque->distance, 2),
                    'عدد_الحلقات' => $mosque->quranCircles->count(),
                    'الحلقات_النشطة' => $mosque->quranCircles->where('circle_status', 'نشطة')->count(),
                ];
            });

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم البحث عن المساجد القريبة بنجاح',
                'البحث_في_نطاق' => "{$radius} كيلومتر",
                'عدد_المساجد_الموجودة' => $mosques->count(),
                'البيانات' => $data
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'بيانات غير صحيحة',
                'الأخطاء' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في البحث عن المساجد القريبة',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ربط معلم بمسجد محدد
     */
    public function assignTeacher(Request $request, $id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);
            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'notes' => 'nullable|string|max:500'
            ], [
                'teacher_id.required' => 'معرف المعلم مطلوب',
                'teacher_id.exists' => 'المعلم غير موجود',
                'notes.max' => 'الملاحظات يجب ألا تتجاوز 500 حرف'
            ]);

            $teacher = Teacher::find($validatedData['teacher_id']);
            
            // التحقق من أن المعلم غير مرتبط بمسجد آخر
            if ($teacher->mosque_id && $teacher->mosque_id != $id) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم مرتبط بالفعل بمسجد آخر: ' . $teacher->mosque->name,
                    'المسجد_الحالي' => [
                        'id' => $teacher->mosque->id,
                        'اسم_المسجد' => $teacher->mosque->name,
                        'الحي' => $teacher->mosque->neighborhood
                    ]
                ], 400);
            }

            // ربط المعلم بالمسجد
            $teacher->mosque_id = $id;
            $teacher->save();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم ربط المعلم بالمسجد بنجاح',
                'البيانات' => [
                    'المعلم' => [
                        'id' => $teacher->id,
                        'الاسم' => $teacher->name,
                        'رقم_الهوية' => $teacher->identity_number,
                        'رقم_الجوال' => $teacher->phone
                    ],
                    'المسجد' => [
                        'id' => $mosque->id,
                        'اسم_المسجد' => $mosque->name,
                        'الحي' => $mosque->neighborhood
                    ],
                    'تاريخ_الربط' => now()->format('Y-m-d H:i:s'),
                    'ملاحظات' => $validatedData['notes'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في ربط المعلم بالمسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء ربط معلم من مسجد محدد
     */
    public function unassignTeacher(Request $request, $id): JsonResponse
    {
        try {
            $mosque = Mosque::find($id);
            if (!$mosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد غير موجود'
                ], 404);
            }

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'reason' => 'nullable|string|max:500'
            ], [
                'teacher_id.required' => 'معرف المعلم مطلوب',
                'teacher_id.exists' => 'المعلم غير موجود',
                'reason.max' => 'سبب إلغاء الربط يجب ألا يتجاوز 500 حرف'
            ]);

            $teacher = Teacher::find($validatedData['teacher_id']);
            
            // التحقق من أن المعلم مرتبط بهذا المسجد
            if ($teacher->mosque_id != $id) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير مرتبط بهذا المسجد',
                    'المسجد_الحالي' => $teacher->mosque_id ? [
                        'id' => $teacher->mosque->id,
                        'اسم_المسجد' => $teacher->mosque->name
                    ] : null
                ], 400);
            }

            $teacherData = [
                'id' => $teacher->id,
                'الاسم' => $teacher->name,
                'رقم_الهوية' => $teacher->identity_number,
                'المسجد_السابق' => [
                    'id' => $mosque->id,
                    'اسم_المسجد' => $mosque->name,
                    'الحي' => $mosque->neighborhood
                ]
            ];

            // إلغاء ربط المعلم من المسجد
            $teacher->mosque_id = null;
            $teacher->save();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم إلغاء ربط المعلم من المسجد بنجاح',
                'البيانات' => [
                    'المعلم' => $teacherData,
                    'تاريخ_إلغاء_الربط' => now()->format('Y-m-d H:i:s'),
                    'سبب_إلغاء_الربط' => $validatedData['reason'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في إلغاء ربط المعلم من المسجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * نقل معلم من مسجد إلى مسجد آخر
     */
    public function transferTeacher(Request $request, $fromMosqueId): JsonResponse
    {
        try {
            $fromMosque = Mosque::find($fromMosqueId);
            if (!$fromMosque) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المسجد المصدر غير موجود'
                ], 404);
            }

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'to_mosque_id' => 'required|exists:mosques,id|different:' . $fromMosqueId,
                'transfer_reason' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:500'
            ], [
                'teacher_id.required' => 'معرف المعلم مطلوب',
                'teacher_id.exists' => 'المعلم غير موجود',
                'to_mosque_id.required' => 'معرف المسجد الوجهة مطلوب',
                'to_mosque_id.exists' => 'المسجد الوجهة غير موجود',
                'to_mosque_id.different' => 'المسجد الوجهة يجب أن يكون مختلفاً عن المسجد المصدر',
                'transfer_reason.max' => 'سبب النقل يجب ألا يتجاوز 500 حرف',
                'notes.max' => 'الملاحظات يجب ألا تتجاوز 500 حرف'
            ]);

            $teacher = Teacher::find($validatedData['teacher_id']);
            $toMosque = Mosque::find($validatedData['to_mosque_id']);

            // التحقق من أن المعلم مرتبط بالمسجد المصدر
            if ($teacher->mosque_id != $fromMosqueId) {
                return response()->json([
                    'نجح' => false,
                    'رسالة' => 'المعلم غير مرتبط بالمسجد المصدر',
                    'المسجد_الحالي' => $teacher->mosque_id ? [
                        'id' => $teacher->mosque->id,
                        'اسم_المسجد' => $teacher->mosque->name
                    ] : null
                ], 400);
            }

            $transferData = [
                'المعلم' => [
                    'id' => $teacher->id,
                    'الاسم' => $teacher->name,
                    'رقم_الهوية' => $teacher->identity_number,
                    'رقم_الجوال' => $teacher->phone
                ],
                'المسجد_المصدر' => [
                    'id' => $fromMosque->id,
                    'اسم_المسجد' => $fromMosque->name,
                    'الحي' => $fromMosque->neighborhood
                ],
                'المسجد_الوجهة' => [
                    'id' => $toMosque->id,
                    'اسم_المسجد' => $toMosque->name,
                    'الحي' => $toMosque->neighborhood
                ]
            ];

            // تنفيذ النقل
            $teacher->mosque_id = $validatedData['to_mosque_id'];
            $teacher->save();

            return response()->json([
                'نجح' => true,
                'رسالة' => 'تم نقل المعلم بين المساجد بنجاح',
                'البيانات' => array_merge($transferData, [
                    'تاريخ_النقل' => now()->format('Y-m-d H:i:s'),
                    'سبب_النقل' => $validatedData['transfer_reason'] ?? null,
                    'ملاحظات' => $validatedData['notes'] ?? null
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'نجح' => false,
                'رسالة' => 'خطأ في نقل المعلم بين المساجد',
                'الخطأ' => $e->getMessage()
            ], 500);
        }
    }
}
