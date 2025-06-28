<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mosque;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\RecitationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * API لوحة معلومات المسجد - للمعلمين
 * محسن للأداء مع استعلامات قليلة
 */
class MosqueDashboardController extends Controller
{
    /**
     * لوحة معلومات المسجد للمعلم
     * GET /api/mosques/{mosque_id}/dashboard?teacher_id={teacher_id}
     */
    public function dashboard($mosque_id, Request $request): JsonResponse
    {
        try {
            $teacher_id = $request->get('teacher_id');
            $today = Carbon::today()->toDateString();

            // التحقق من وجود المسجد
            $mosque = Mosque::find($mosque_id);
            if (!$mosque) {
                return response()->json([
                    'success' => false,
                    'message' => 'المسجد غير موجود'
                ], 404);
            }

            // التحقق من وجود المعلم (اختياري)
            if ($teacher_id) {
                $teacher = Teacher::find($teacher_id);
                if (!$teacher) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المعلم غير موجود'
                    ], 404);
                }
            }            // الحصول على الطلاب
            $studentsQuery = Student::where('mosque_id', $mosque_id);
            
            // فلترة حسب المعلم إذا تم تحديده
            if ($teacher_id) {
                $studentsQuery->whereHas('quranCircle.activeTeachers', function($q) use ($teacher_id) {
                    $q->where('teachers.id', $teacher_id);
                });
            }

            $students = $studentsQuery->select('id', 'name', 'quran_circle_id')->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد طلاب',
                    'data' => [
                        'mosque' => [
                            'id' => $mosque->id,
                            'name' => $mosque->name
                        ],
                        'students' => [],
                        'attendance_today' => [],
                        'attendance_stats' => [
                            'total_students' => 0,
                            'present' => 0,
                            'absent' => 0,
                            'late' => 0,
                            'excused' => 0,
                            'attendance_rate' => 0
                        ]
                    ]
                ], 200);
            }

            $student_ids = $students->pluck('id')->toArray();

            // الحصول على حضور اليوم لجميع الطلاب بـ استعلام واحد
            $attendanceToday = Attendance::where('attendable_type', 'App\\Models\\Student')
                ->whereIn('attendable_id', $student_ids)
                ->where('date', $today)
                ->select('attendable_id', 'status')
                ->get()
                ->keyBy('attendable_id');

            // تجهيز البيانات للاستجابة
            $attendance_today_response = [];
            $attendance_stats = [
                'total_students' => $students->count(),
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'not_recorded' => 0
            ];

            foreach ($students as $student) {
                $attendance = $attendanceToday->get($student->id);
                if ($attendance) {
                    $status = $attendance->status;
                    $attendance_today_response[$student->name] = $status;
                    
                    // إحصائيات الحضور
                    switch ($status) {
                        case 'حاضر':
                            $attendance_stats['present']++;
                            break;
                        case 'غائب':
                            $attendance_stats['absent']++;
                            break;
                        case 'متأخر':
                            $attendance_stats['late']++;
                            break;
                        case 'مأذون':
                            $attendance_stats['excused']++;
                            break;
                    }
                } else {
                    $attendance_today_response[$student->name] = 'غير مسجل';
                    $attendance_stats['not_recorded']++;
                }
            }

            // حساب معدل الحضور
            $recorded_attendance = $attendance_stats['present'] + $attendance_stats['late'];
            $attendance_stats['attendance_rate'] = $attendance_stats['total_students'] > 0 
                ? round(($recorded_attendance / $attendance_stats['total_students']) * 100, 1)
                : 0;

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات لوحة المعلومات بنجاح',
                'data' => [
                    'mosque' => [
                        'id' => $mosque->id,
                        'name' => $mosque->name
                    ],
                    'teacher_id' => $teacher_id,
                    'date' => $today,
                    'students' => $students->map(function($student) {
                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'circle_id' => $student->quran_circle_id
                        ];
                    }),
                    'attendance_today' => $attendance_today_response,
                    'attendance_stats' => $attendance_stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات لوحة المعلومات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حضور اليوم فقط - مبسط أكثر
     * GET /api/mosques/{mosque_id}/attendance-today?teacher_id={teacher_id}
     */
    public function attendanceToday($mosque_id, Request $request): JsonResponse
    {
        try {
            $teacher_id = $request->get('teacher_id');
            $today = Carbon::today()->toDateString();            // الحصول على الطلاب
            $studentsQuery = Student::where('mosque_id', $mosque_id);
            
            if ($teacher_id) {
                $studentsQuery->whereHas('quranCircle.activeTeachers', function($q) use ($teacher_id) {
                    $q->where('teachers.id', $teacher_id);
                });
            }

            $students = $studentsQuery->select('id', 'name')->get();
            $student_ids = $students->pluck('id')->toArray();

            // حضور اليوم - استعلام واحد
            $attendanceToday = Attendance::where('attendable_type', 'App\\Models\\Student')
                ->whereIn('attendable_id', $student_ids)
                ->where('date', $today)
                ->select('attendable_id', 'status')
                ->get()
                ->keyBy('attendable_id');

            $result = [];
            foreach ($students as $student) {
                $attendance = $attendanceToday->get($student->id);
                $result[$student->name] = $attendance ? $attendance->status : 'غير مسجل';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $today,
                    'attendance' => $result
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
