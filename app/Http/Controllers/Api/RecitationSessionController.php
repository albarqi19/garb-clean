<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Models\User;
use App\Models\QuranCircle;
use App\Models\StudentProgress;
use App\Services\DailyCurriculumTrackingService;
use App\Services\FlexibleProgressionService;
use App\Services\FlexibleCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecitationSessionController extends Controller
{
    protected $curriculumTrackingService;
    protected $progressionService;
    protected $curriculumService;

    public function __construct(
        DailyCurriculumTrackingService $curriculumTrackingService,
        FlexibleProgressionService $progressionService,
        FlexibleCurriculumService $curriculumService
    ) {
        $this->curriculumTrackingService = $curriculumTrackingService;
        $this->progressionService = $progressionService;
        $this->curriculumService = $curriculumService;
    }

    /**
     * عرض جميع جلسات التسميع
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecitationSession::with(['student', 'teacher', 'circle']);

        // فلترة حسب الطالب
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // فلترة حسب المعلم
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sessions = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'تم جلب جلسات التسميع بنجاح'
        ]);
    }

    /**
     * إنشاء جلسة تسميع جديدة
     */    public function store(Request $request): JsonResponse
    {
        // Force JSON input handling for API requests
        if ($request->isMethod('POST') && $request->header('Content-Type') === 'application/json') {
            $jsonData = json_decode($request->getContent(), true);
            if ($jsonData && is_array($jsonData)) {
                $request->merge($jsonData);
            }
        }
        
        // للتشخيص: لنرى ما يستقبله الـ Controller فعلياً
        Log::info('Request Debug', [
            'all_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'is_json' => $request->isJson(),
            'json_decoded' => json_decode($request->getContent(), true)
        ]);
        
        // إقرأ البيانات من JSON إذا كان الـ request فارغ
        $requestData = $request->all();
        if (empty($requestData) && $request->isJson()) {
            $requestData = json_decode($request->getContent(), true) ?? [];
        }
          Log::info('Final Request Data', ['data' => $requestData]);

        $validator = Validator::make($requestData, [
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:users,id',
            'quran_circle_id' => 'required|exists:quran_circles,id',
            'start_surah_number' => 'required|integer|min:1|max:114',
            'start_verse' => 'required|integer|min:1',
            'end_surah_number' => 'required|integer|min:1|max:114',
            'end_verse' => 'required|integer|min:1',
            'recitation_type' => 'required|in:حفظ,مراجعة صغرى,مراجعة كبرى,تثبيت',
            'duration_minutes' => 'nullable|integer|min:1',
            'grade' => 'required|numeric|min:0|max:10',
            'evaluation' => 'required|in:ممتاز,جيد جداً,جيد جدا,جيد,مقبول,ضعيف',
            'teacher_notes' => 'nullable|string|max:1000',            'curriculum_id' => 'nullable|exists:curricula,id',
            'status' => 'nullable|in:جارية,غير مكتملة,مكتملة'
        ]);        if ($validator->fails()) {
            Log::error('Validation Failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $requestData
            ]);
              return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
                'debug_info' => [
                    'received_data' => $requestData,
                    'validation_rules' => [
                        'evaluation_valid_values' => ['ممتاز', 'جيد جداً', 'جيد جدا', 'جيد', 'مقبول', 'ضعيف'],
                        'recitation_type_valid_values' => ['حفظ', 'مراجعة صغرى', 'مراجعة كبرى', 'تثبيت'],
                        'status_valid_values' => ['جارية', 'غير مكتملة', 'مكتملة']
                    ]
                ]
            ], 422);
        }        try {
            DB::beginTransaction();            // إعداد البيانات للإنشاء
            $sessionData = $requestData;
            
            // توليد session_id فريد
            $sessionData['session_id'] = 'session_' . time() . '_' . uniqid();
            
            // إذا لم يتم تحديد المنهج، ابحث عن المنهج الحالي للطالب
            if (!isset($sessionData['curriculum_id'])) {
                $studentProgress = StudentProgress::where('student_id', $requestData['student_id'])
                    ->where('is_active', true)
                    ->first();
                
                if ($studentProgress) {
                    $sessionData['curriculum_id'] = $studentProgress->curriculum_id;
                }
            }

            // تعيين الحالة الافتراضية
            if (!isset($sessionData['status'])) {
                $sessionData['status'] = 'جارية';
            }            // إنشاء جلسة التسميع
            $session = RecitationSession::create($sessionData);

            DB::commit();

            // إرجاع البيانات الأساسية فقط بدون العلاقات الحساسة
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء جلسة التسميع بنجاح',
                'data' => [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'student_id' => $session->student_id,
                    'teacher_id' => $session->teacher_id,
                    'quran_circle_id' => $session->quran_circle_id,
                    'curriculum_id' => $session->curriculum_id,
                    'start_surah_number' => $session->start_surah_number,
                    'start_verse' => $session->start_verse,
                    'end_surah_number' => $session->end_surah_number,
                    'end_verse' => $session->end_verse,
                    'recitation_type' => $session->recitation_type,
                    'duration_minutes' => $session->duration_minutes,
                    'grade' => $session->grade,
                    'evaluation' => $session->evaluation,
                    'status' => $session->status,
                    'teacher_notes' => $session->teacher_notes,
                    'has_errors' => $session->has_errors ?? false,
                    'total_verses' => $session->total_verses,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at,
                ]
            ], 201);} catch (\Exception $e) {
            DB::rollBack();
              Log::error('Session Creation Failed', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $requestData
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء جلسة التسميع',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }    /**
     * عرض جلسة تسميع محددة باستخدام session_id
     */
    public function show(string $sessionId): JsonResponse
    {
        try {
            $session = RecitationSession::with(['student', 'teacher', 'circle', 'errors'])
                ->where('session_id', $sessionId)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'جلسة التسميع غير موجودة'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'تم جلب جلسة التسميع بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب جلسة التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث جلسة تسميع
     */    public function update(Request $request, string $sessionId): JsonResponse
    {
        // Force JSON input handling for API requests
        if ($request->isMethod('PUT') && $request->header('Content-Type') === 'application/json') {
            $jsonData = json_decode($request->getContent(), true);
            if ($jsonData && is_array($jsonData)) {
                $request->merge($jsonData);
            }
        }
        
        // للتشخيص: لنرى ما يستقبله الـ Controller فعلياً
        Log::info('Update Request Debug', [
            'session_id' => $sessionId,
            'all_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'is_json' => $request->isJson(),
            'json_decoded' => json_decode($request->getContent(), true)
        ]);
        
        // إقرأ البيانات من JSON إذا كان الـ request فارغ
        $requestData = $request->all();
        if (empty($requestData) && $request->isJson()) {
            $requestData = json_decode($request->getContent(), true) ?? [];
        }
        Log::info('Final Update Request Data', ['data' => $requestData]);        $validator = Validator::make($requestData, [
            'grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'teacher_notes' => 'nullable|string|max:1000',
            'has_errors' => 'nullable|boolean',
            'correction_notes' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|numeric|min:0.1|max:300',
            'evaluation' => 'nullable|string|max:255',
            'status' => 'nullable|in:جارية,غير مكتملة,مكتملة'
        ]);

        if ($validator->fails()) {
            Log::error('Update Validation Failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $requestData
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $session = RecitationSession::where('session_id', $sessionId)->first();
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'جلسة التسميع غير موجودة'
                ], 404);
            }            $oldStatus = $session->status;
              // تحديث البيانات باستخدام البيانات المُستخرجة
            $updateData = array_intersect_key($requestData, array_flip(['grade', 'notes', 'teacher_notes', 'has_errors', 'correction_notes', 'duration_minutes', 'evaluation', 'status']));
            $session->update($updateData);

            // إذا تم تغيير الحالة إلى "مكتملة"، قم بتحديث تقدم الطالب
            if (isset($requestData['status']) && $requestData['status'] === 'مكتملة' && $oldStatus !== 'مكتملة') {
                Log::info('Session completed, updating student progress', [
                    'session_id' => $session->session_id,
                    'student_id' => $session->student_id
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $session->fresh(),
                'message' => 'تم تحديث جلسة التسميع بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث جلسة التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث حالة جلسة التسميع
     */
    public function updateStatus(Request $request, string $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:جارية,غير مكتملة,مكتملة'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $session = RecitationSession::where('session_id', $sessionId)->first();
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'جلسة التسميع غير موجودة'
                ], 404);
            }

            $oldStatus = $session->status;
            $newStatus = $request->status;

            $session->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->session_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'updated_at' => $session->updated_at
                ],
                'message' => 'تم تحديث حالة الجلسة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الجلسة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على المحتوى التالي المقترح للطالب
     */    public function getNextRecitationContent(int $studentId): JsonResponse
    {
        try {
            // البحث عن الطالب
            $student = Student::with(['curricula' => function ($query) {
                $query->where('status', 'قيد التنفيذ')
                      ->with(['curriculum']);
            }])->find($studentId);
            
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

            // البحث عن التقدم الحالي
            $currentProgress = DB::table('student_curriculum_progress')
                ->where('student_curriculum_id', $activeCurriculum->id)
                ->where('status', 'قيد التنفيذ')
                ->first();

            if (!$currentProgress) {
                // إذا لم يوجد تقدم، نأخذ أول خطة
                $firstPlan = DB::table('curriculum_plans')
                    ->where('curriculum_id', $activeCurriculum->curriculum_id)
                    ->orderBy('id')
                    ->first();

                if (!$firstPlan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا توجد خطط متاحة لهذا المنهج'
                    ], 404);
                }

                $nextContent = [
                    'content' => $firstPlan->content,
                    'type' => $firstPlan->plan_type,
                    'expected_days' => $firstPlan->expected_days,
                    'is_first_lesson' => true
                ];
            } else {
                // البحث عن الخطة التالية
                $nextPlan = DB::table('curriculum_plans')
                    ->where('curriculum_id', $activeCurriculum->curriculum_id)
                    ->where('id', '>', $currentProgress->curriculum_plan_id)
                    ->orderBy('id')
                    ->first();

                if (!$nextPlan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تم إكمال جميع خطط المنهج'
                    ], 200);
                }

                $nextContent = [
                    'content' => $nextPlan->content,
                    'type' => $nextPlan->plan_type,
                    'expected_days' => $nextPlan->expected_days,
                    'is_first_lesson' => false
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $nextContent,
                'message' => 'تم جلب المحتوى التالي للتسميع بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المحتوى التالي',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقييم استعداد الطالب للانتقال إلى منهج جديد
     */
    public function evaluateStudentProgression(int $studentId): JsonResponse
    {
        try {
            $student = Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            $evaluation = $this->progressionService->evaluateProgressionReadiness($student);

            return response()->json([
                'success' => true,
                'data' => $evaluation,
                'message' => 'تم تقييم استعداد الطالب بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تقييم استعداد الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات عامة للتسميع
     */
    public function getStatsSummary(): JsonResponse
    {
        try {
            $totalSessions = RecitationSession::count();
            $sessionsWithErrors = RecitationSession::where('has_errors', true)->count();
            $averageGrade = RecitationSession::whereNotNull('grade')->avg('grade');
            $todaySessions = RecitationSession::whereDate('created_at', today())->count();

            // إحصائيات الحالات
            $statusStats = RecitationSession::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $stats = [
                'total_sessions' => $totalSessions,
                'sessions_with_errors' => $sessionsWithErrors,
                'sessions_without_errors' => $totalSessions - $sessionsWithErrors,
                'error_rate_percentage' => $totalSessions > 0 ? round(($sessionsWithErrors / $totalSessions) * 100, 2) : 0,
                'average_grade' => round($averageGrade ?? 0, 2),
                'today_sessions' => $todaySessions,
                'status_breakdown' => [
                    'ongoing' => $statusStats['جارية'] ?? 0,
                    'incomplete' => $statusStats['غير مكتملة'] ?? 0,
                    'completed' => $statusStats['مكتملة'] ?? 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب الإحصائيات بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات طالب محدد
     */
    public function getStudentStats(int $studentId): JsonResponse
    {
        try {
            $student = Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            $totalSessions = RecitationSession::where('student_id', $studentId)->count();
            $sessionsWithErrors = RecitationSession::where('student_id', $studentId)->where('has_errors', true)->count();
            $averageGrade = RecitationSession::where('student_id', $studentId)->whereNotNull('grade')->avg('grade');
            $lastSession = RecitationSession::where('student_id', $studentId)->latest()->first();

            // إحصائيات الحالات للطالب
            $statusStats = RecitationSession::where('student_id', $studentId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $stats = [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'identity_number' => $student->identity_number
                ],
                'total_sessions' => $totalSessions,
                'sessions_with_errors' => $sessionsWithErrors,
                'sessions_without_errors' => $totalSessions - $sessionsWithErrors,
                'error_rate_percentage' => $totalSessions > 0 ? round(($sessionsWithErrors / $totalSessions) * 100, 2) : 0,
                'average_grade' => round($averageGrade ?? 0, 2),
                'last_session_date' => $lastSession ? $lastSession->created_at->format('Y-m-d H:i:s') : null,
                'status_breakdown' => [
                    'ongoing' => $statusStats['جارية'] ?? 0,
                    'incomplete' => $statusStats['غير مكتملة'] ?? 0,
                    'completed' => $statusStats['مكتملة'] ?? 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب إحصائيات الطالب بنجاح'
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
     * إحصائيات معلم محدد
     */
    public function getTeacherStats(int $teacherId): JsonResponse
    {
        try {
            $teacher = User::find($teacherId);
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المعلم غير موجود'
                ], 404);
            }

            $totalSessions = RecitationSession::where('teacher_id', $teacherId)->count();
            $sessionsWithErrors = RecitationSession::where('teacher_id', $teacherId)->where('has_errors', true)->count();
            $averageGrade = RecitationSession::where('teacher_id', $teacherId)->whereNotNull('grade')->avg('grade');
            $studentsCount = RecitationSession::where('teacher_id', $teacherId)->distinct('student_id')->count();

            $stats = [
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email
                ],
                'total_sessions' => $totalSessions,
                'sessions_with_errors' => $sessionsWithErrors,
                'sessions_without_errors' => $totalSessions - $sessionsWithErrors,
                'error_rate_percentage' => $totalSessions > 0 ? round(($sessionsWithErrors / $totalSessions) * 100, 2) : 0,
                'average_grade' => round($averageGrade ?? 0, 2),
                'students_taught' => $studentsCount
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب إحصائيات المعلم بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف جلسة تسميع محددة
     */
    public function destroy($sessionId): JsonResponse
    {
        try {
            // البحث عن الجلسة باستخدام session_id
            $session = RecitationSession::where('session_id', $sessionId)->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'جلسة التسميع غير موجودة'
                ], 404);
            }

            // حذف الأخطاء المرتبطة بالجلسة أولاً
            $session->errors()->delete();

            // حذف الجلسة
            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف جلسة التسميع بنجاح',
                'deleted_session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف جلسة التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
