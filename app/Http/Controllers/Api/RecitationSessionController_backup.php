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
     */
    public function store(Request $request): JsonResponse
    {
        // للتشخيص: لنرى ما يستقبله الـ Controller فعلياً
        \Log::info('Request Debug', [
            'all_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'is_json' => $request->isJson(),
            'json_decoded' => json_decode($request->getContent(), true)
        ]);

        $validator = Validator::make($request->all(), [
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
            'evaluation' => 'required|in:ممتاز,جيد جداً,جيد,مقبول,ضعيف',
            'teacher_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // إنشاء جلسة التسميع (session_id سيتم توليده تلقائياً)
            $session = RecitationSession::create($request->all());

            // ربط الجلسة بالمنهج الدراسي الحالي
            $this->curriculumTrackingService->trackCurriculumForSession($session);

            // جلب البيانات مع العلاقات
            $session->load(['student', 'teacher', 'circle']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'تم إنشاء جلسة التسميع بنجاح',
                'session_id' => $session->session_id // إرجاع session_id للاستخدام في إضافة الأخطاء
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء جلسة التسميع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
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
     */
    public function update(Request $request, string $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'has_errors' => 'nullable|boolean',
            'correction_notes' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:300',
            'evaluation' => 'nullable|string|max:255'
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

            $session->update($request->only(['grade', 'notes', 'has_errors', 'correction_notes', 'duration_minutes', 'evaluation']));

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
     * إحصائيات عامة للتسميع
     */
    public function getStatsSummary(): JsonResponse
    {
        try {
            $totalSessions = RecitationSession::count();
            $sessionsWithErrors = RecitationSession::where('has_errors', true)->count();
            $averageGrade = RecitationSession::whereNotNull('grade')->avg('grade');
            $todaySessions = RecitationSession::whereDate('created_at', today())->count();

            $stats = [
                'total_sessions' => $totalSessions,
                'sessions_with_errors' => $sessionsWithErrors,
                'sessions_without_errors' => $totalSessions - $sessionsWithErrors,
                'error_rate_percentage' => $totalSessions > 0 ? round(($sessionsWithErrors / $totalSessions) * 100, 2) : 0,
                'average_grade' => round($averageGrade ?? 0, 2),
                'today_sessions' => $todaySessions
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
                'last_session_date' => $lastSession ? $lastSession->created_at->format('Y-m-d H:i:s') : null
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
