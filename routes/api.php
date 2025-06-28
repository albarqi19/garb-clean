<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\RecitationSessionController;
use App\Http\Controllers\Api\RecitationErrorController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\MosqueController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\MosqueDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// مسارات المصادقة للمعلمين والطلاب
Route::prefix('auth')->group(function () {
    // تسجيل الدخول العام (للمشرفين والمدراء)
    Route::post('/login', [AuthController::class, 'login']);
    
    // تسجيل الدخول للمشرفين
    Route::post('/supervisor/login', [AuthController::class, 'supervisorLogin']);
    
    // تسجيل الدخول
    Route::post('/teacher/login', [AuthController::class, 'teacherLogin']);
    Route::post('/student/login', [AuthController::class, 'studentLogin']);
    
    // تغيير كلمة المرور
    Route::post('/teacher/change-password', [AuthController::class, 'teacherChangePassword']);
    Route::post('/student/change-password', [AuthController::class, 'studentChangePassword']);
    
    // معلومات المستخدم
    Route::post('/user-info', [AuthController::class, 'getUserInfo']);
});

// مسارات حضور الطلاب
Route::prefix('attendance')->group(function () {
    // تسجيل حضور طالب (للأنظمة الخارجية)
    Route::post('/record', [StudentAttendanceController::class, 'store']);
    
    // تسجيل حضور متعدد الطلاب (للواجهة الأمامية)
    Route::post('/record-batch', [StudentAttendanceController::class, 'storeBatch']);
    
    // الحصول على سجلات الحضور (اختياري)
    Route::get('/records', [StudentAttendanceController::class, 'index']);
    
    // إحصائيات الحضور (اختياري)
    Route::get('/stats', [StudentAttendanceController::class, 'stats']);
});

// مسارات نظام التسميع
Route::prefix('recitation')->middleware('api')->group(function () {
    // مسارات جلسات التسميع
    Route::prefix('sessions')->group(function () {
        // إنشاء جلسة تسميع جديدة
        Route::post('/', [RecitationSessionController::class, 'store']);
          // عرض جلسات التسميع مع الفلترة
        Route::get('/', [RecitationSessionController::class, 'index']);
        
        // إحصائيات عامة
        Route::get('/stats/summary', [RecitationSessionController::class, 'getStatsSummary']);
          // إحصائيات طالب محدد
        Route::get('/stats/student/{studentId}', [RecitationSessionController::class, 'getStudentStats']);
          // إحصائيات معلم محدد
        Route::get('/stats/teacher/{teacherId}', [RecitationSessionController::class, 'getTeacherStats']);
        
        // عرض تفاصيل جلسة تسميع باستخدام session_id
        Route::get('/{sessionId}', [RecitationSessionController::class, 'show']);
          // تحديث جلسة تسميع
        Route::put('/{sessionId}', [RecitationSessionController::class, 'update']);
        
        // حذف جلسة تسميع
        Route::delete('/{sessionId}', [RecitationSessionController::class, 'destroy']);
        
        // تحديث حالة جلسة التسميع
        Route::patch('/{sessionId}/status', [RecitationSessionController::class, 'updateStatus']);
        
        // الحصول على المحتوى التالي المقترح للطالب
        Route::get('/next-content/{studentId}', [RecitationSessionController::class, 'getNextRecitationContent']);
        
        // تقييم استعداد الطالب للانتقال إلى منهج جديد
        Route::get('/evaluate-progression/{studentId}', [RecitationSessionController::class, 'evaluateStudentProgression']);
    });
    
    // مسارات أخطاء التسميع
    Route::prefix('errors')->group(function () {
        // إضافة أخطاء لجلسة تسميع
        Route::post('/', [RecitationErrorController::class, 'store']);
        
        // عرض أخطاء جلسة معينة
        Route::get('/', [RecitationErrorController::class, 'index']);
        
        // إحصائيات الأخطاء الشائعة
        Route::get('/stats/common', [RecitationErrorController::class, 'getCommonErrors']);
          // إحصائيات أخطاء طالب محدد
        Route::get('/stats/student/{studentId}', [RecitationErrorController::class, 'getStudentErrors']);
    });
});

/*
|--------------------------------------------------------------------------
| مسارات API شاملة للمعلمين والطلاب والحلقات
|--------------------------------------------------------------------------
*/

// مسارات المعلمين - APIs للمعلمين وبياناتهم
Route::prefix('teachers')->group(function () {
    // قائمة المعلمين
    Route::get('/', [TeacherController::class, 'index']);
    
    // تفاصيل معلم محدد
    Route::get('/{id}', [TeacherController::class, 'show']);
      // حلقات معلم محدد
    Route::get('/{id}/circles', [TeacherController::class, 'teacherCircles']);
      // طلاب معلم محدد
    Route::get('/{id}/students', [TeacherController::class, 'getStudents']);
    
    // طلاب معلم محدد من مسجد محدد
    Route::get('/{teacherId}/mosques/{mosqueId}/students', [TeacherController::class, 'getTeacherStudentsFromMosque']);
    
    // المساجد التي يعمل بها المعلم مع جداوله وحلقاته والطلاب
    Route::get('/{id}/mosques', [TeacherController::class, 'getTeacherMosques']);
    
    // حلقات المعلم مع تفاصيل الطلاب الشاملة
    Route::get('/{id}/circles-detailed', [TeacherController::class, 'getTeacherCirclesDetailed']);
    
    // إحصائيات معلم محدد
    Route::get('/{id}/stats', [TeacherController::class, 'teacherStats']);
    
    // سجل حضور معلم محدد
    Route::get('/{id}/attendance', [TeacherController::class, 'teacherAttendance']);
    
    // رواتب وحوافز معلم محدد
    Route::get('/{id}/financials', [TeacherController::class, 'teacherFinancials']);
});

// مسارات الطلاب - APIs للطلاب وبياناتهم
Route::prefix('students')->group(function () {
    // قائمة الطلاب
    Route::get('/', [StudentController::class, 'index']);
    
    // تفاصيل طالب محدد
    Route::get('/{id}', [StudentController::class, 'show']);
    
    // منهج طالب محدد
    Route::get('/{id}/curriculum', [StudentController::class, 'studentCurriculum']);
    
    // منهج الطالب اليومي - جديد
    Route::get('/{id}/daily-curriculum', [StudentController::class, 'getDailyCurriculum']);
    
    // إكمال تسميع اليوم والانتقال لليوم التالي - جديد  
    Route::post('/{id}/complete-daily-recitation', [StudentController::class, 'completeRecitation']);
    
    // إحصائيات طالب محدد
    Route::get('/{id}/stats', [StudentController::class, 'studentStats']);
    
    // سجل حضور طالب محدد
    Route::get('/{id}/attendance', [StudentController::class, 'studentAttendance']);
      // جلسات تسميع طالب محدد
    Route::get('/{id}/recitation-sessions', [StudentController::class, 'studentRecitationSessions']);
    
    // آخر جلسة تسميع للطالب
    Route::get('/{id}/last-recitation', [StudentController::class, 'getLastRecitation']);
});

// مسارات الحلقات - APIs للحلقات وبياناتها
Route::prefix('circles')->group(function () {
    // قائمة الحلقات
    Route::get('/', [CircleController::class, 'index']);
    
    // تفاصيل حلقة محددة
    Route::get('/{id}', [CircleController::class, 'show']);
    
    // إحصائيات حلقة محددة
    Route::get('/{id}/stats', [CircleController::class, 'circleStats']);
    
    // حلقات معلم محدد
    Route::get('/teacher/{teacherId}', [CircleController::class, 'circlesByTeacher']);    // أفضل الحلقات من حيث الأداء
    Route::get('/top-performing', [CircleController::class, 'topPerformingCircles']);
});

// مسارات المساجد - APIs للمساجد وبياناتها
Route::prefix('mosques')->group(function () {
    // قائمة المساجد مع الفلترة والبحث
    Route::get('/', [MosqueController::class, 'index']);
    
    // إنشاء مسجد جديد
    Route::post('/', [MosqueController::class, 'store']);
    
    // تفاصيل مسجد محدد
    Route::get('/{id}', [MosqueController::class, 'show']);
    
    // تحديث بيانات مسجد
    Route::put('/{id}', [MosqueController::class, 'update']);
    
    // حذف مسجد
    Route::delete('/{id}', [MosqueController::class, 'destroy']);
    
    // حلقات مسجد محدد
    Route::get('/{id}/circles', [MosqueController::class, 'circles']);
    
    // معلمي مسجد محدد
    Route::get('/{id}/teachers', [MosqueController::class, 'teachers']);
    
    // طلاب مسجد محدد
    Route::get('/{id}/students', [MosqueController::class, 'students']);
      // إحصائيات مسجد محدد
    Route::get('/{id}/statistics', [MosqueController::class, 'statistics']);
    
    // لوحة تحكم المسجد - عرض سريع للطلاب وحضور اليوم
    Route::get('/{id}/dashboard', [MosqueController::class, 'dashboard']);
    
    // البحث عن المساجد القريبة
    Route::get('/nearby/search', [MosqueController::class, 'nearby']);
    
    // ربط وإلغاء ربط المعلمين بالمساجد
    Route::post('/{id}/assign-teacher', [MosqueController::class, 'assignTeacher']);
    Route::post('/{id}/unassign-teacher', [MosqueController::class, 'unassignTeacher']);
    Route::post('/{id}/transfer-teacher', [MosqueController::class, 'transferTeacher']);
});

// مسارات لوحة معلومات المسجد - محسنة للأداء
Route::prefix('mosques')->group(function () {
    // لوحة معلومات المسجد الشاملة
    Route::get('/{mosque_id}/teacher-dashboard', [MosqueDashboardController::class, 'dashboard']);
    
    // حضور اليوم فقط - مبسط
    Route::get('/{mosque_id}/attendance-today', [MosqueDashboardController::class, 'attendanceToday']);
});

// مسارات التقارير والإحصائيات العامة
Route::prefix('reports')->group(function () {
    // الإحصائيات العامة للنظام
    Route::get('/general-stats', [ReportsController::class, 'generalStats']);
    
    // تقرير الأداء الشهري
    Route::get('/monthly-performance', [ReportsController::class, 'monthlyPerformanceReport']);
    
    // إحصائيات المساجد
    Route::get('/mosque-stats', [ReportsController::class, 'mosqueStats']);
});

// مسار اختبار للتشخيص
Route::post('/test-request', [TestController::class, 'testRequest']);

// مسار مؤقت لحل مشكلة API JSON handling
Route::post('/recitation/sessions/direct', function(\Illuminate\Http\Request $request) {
    try {
        // قراءة البيانات من JSON مباشرة
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'received_content' => $request->getContent()
            ], 400);
        }
        
        // إضافة session_id فريد
        $data['session_id'] = 'direct_' . time() . '_' . uniqid();
        
        // إضافة القيم الافتراضية
        if (!isset($data['status'])) {
            $data['status'] = 'جارية';
        }
        
        // إنشاء الجلسة مباشرة
        $session = \App\Models\RecitationSession::create($data);
        
        // تحميل العلاقات
        $session->load(['student', 'teacher', 'circle']);
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'تم إنشاء جلسة التسميع بنجاح (direct route)',
            'session_id' => $session->session_id
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الجلسة',
            'error' => $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// مسارات API للمشرفين (بدون middleware للاختبار)
Route::prefix('supervisors')->group(function () {
    // قائمة جميع المشرفين
    Route::get('/', [App\Http\Controllers\Api\SupervisorController::class, 'index']);
    
    // إحصائيات المشرفين العامة (يجب أن تكون قبل route المعرف بـ ID)
    Route::get('/statistics', [App\Http\Controllers\Api\SupervisorController::class, 'getStatistics']);
    
    // جلب مشرف محدد بالـ ID
    Route::get('/{id}', [App\Http\Controllers\Api\SupervisorController::class, 'show']);
    
    // الحلقات المشرف عليها
    Route::get('/circles', [App\Http\Controllers\Api\SupervisorController::class, 'getAssignedCircles']);
    
    // طلاب حلقة محددة
    Route::get('/circles/{circleId}/students', [App\Http\Controllers\Api\SupervisorController::class, 'getCircleStudents']);
    
    // معلمي حلقة محددة
    Route::get('/circles/{circleId}/teachers', [App\Http\Controllers\Api\SupervisorController::class, 'getCircleTeachers']);
    
    // إدارة حضور المعلمين
    Route::post('/teacher-attendance', [App\Http\Controllers\Api\SupervisorController::class, 'recordTeacherAttendance']);
    
    // إدارة تقارير وتقييم المعلمين
    Route::post('/teacher-report', [App\Http\Controllers\Api\SupervisorController::class, 'createTeacherReport']);
    Route::get('/teacher-report/{teacherId}', [App\Http\Controllers\Api\SupervisorController::class, 'getTeacherFullReport']);
    
    // إدارة تقييمات المعلمين - Teacher Evaluations Management
    Route::post('/teacher-evaluations', [App\Http\Controllers\Api\SupervisorController::class, 'createTeacherEvaluation']);
    Route::get('/teacher-evaluations/{teacherId}', [App\Http\Controllers\Api\SupervisorController::class, 'getTeacherEvaluations']);
    Route::put('/teacher-evaluations/{evaluationId}', [App\Http\Controllers\Api\SupervisorController::class, 'updateTeacherEvaluation']);
    Route::post('/teacher-evaluations/{evaluationId}/approve', [App\Http\Controllers\Api\SupervisorController::class, 'approveTeacherEvaluation']);
    Route::delete('/teacher-evaluations/{evaluationId}', [App\Http\Controllers\Api\SupervisorController::class, 'deleteTeacherEvaluation']);
    
    // إدارة طلبات نقل الطلاب
    Route::post('/student-transfer', [App\Http\Controllers\Api\SupervisorController::class, 'requestStudentTransfer']);
    Route::get('/transfer-requests', [App\Http\Controllers\Api\SupervisorController::class, 'getTransferRequests']);
    Route::post('/transfer-requests/{requestId}/approve', [App\Http\Controllers\Api\SupervisorController::class, 'approveTransferRequest']);
    Route::post('/transfer-requests/{requestId}/reject', [App\Http\Controllers\Api\SupervisorController::class, 'rejectTransferRequest']);    // إحصائيات المشرف
    Route::get('/dashboard-stats', [App\Http\Controllers\Api\SupervisorController::class, 'getDashboardStats']);
    
    // البيانات الشاملة للمشرف (المسجد + المدرسة القرآنية + الحلقة الفرعية + المعلم + الطلاب)
    Route::get('/comprehensive-overview', [App\Http\Controllers\Api\SupervisorController::class, 'getComprehensiveOverview']);
});

// مسار لوحة تحكم المشرف (مفرد)
Route::prefix('supervisor')->group(function () {
    // لوحة تحكم المشرف
    Route::get('/dashboard', [App\Http\Controllers\Api\SupervisorController::class, 'supervisorDashboard']);
    
    // جلب حلقات المشرف
    Route::get('/circles', [App\Http\Controllers\Api\SupervisorController::class, 'getAssignedCircles']);
    
    // جلب معلمين المشرف
    Route::get('/teachers', [App\Http\Controllers\Api\SupervisorController::class, 'getSupervisorTeachers']);
    
    // جلب طلاب المشرف
    Route::get('/students', [App\Http\Controllers\Api\SupervisorController::class, 'getSupervisorStudents']);
    
    // البيانات الشاملة للمشرف - النظرة الشاملة
    Route::get('/comprehensive-overview', [App\Http\Controllers\Api\SupervisorController::class, 'getComprehensiveOverview']);
});