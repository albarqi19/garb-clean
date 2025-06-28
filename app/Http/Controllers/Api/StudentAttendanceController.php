<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StudentAttendanceController extends Controller
{
    /**
     * تحويل حالات الحضور من الإنجليزية إلى العربية
     */
    private function convertStatusToArabic($status)
    {
        $statusMap = [
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'excused' => 'مأذون'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Store attendance record from external API
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'student_name' => 'required|string|max:255',
                'date' => 'required|date',
                'status' => 'required|in:present,absent,late,excused',
                'period' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Find student by name (assuming name is unique, or you can use student_id)
            $student = Student::where('name', $validatedData['student_name'])->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with name: ' . $validatedData['student_name']
                ], 404);
            }

            // Check if attendance record already exists for this student and date
            $existingAttendance = StudentAttendance::where('attendable_id', $student->id)
                ->where('attendable_type', Student::class)
                ->where('date', $validatedData['date'])
                ->when($validatedData['period'] ?? null, function ($query, $period) {
                    return $query->where('period', $period);
                })
                ->first();

            // Convert status to Arabic
            $arabicStatus = $this->convertStatusToArabic($validatedData['status']);

            if ($existingAttendance) {
                // Update existing record
                $existingAttendance->update([
                    'status' => $arabicStatus,
                    'period' => $validatedData['period'] ?? null,
                    'notes' => $validatedData['notes'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Attendance record updated successfully',
                    'data' => $existingAttendance->fresh()
                ], 200);
            } else {
                // Create new attendance record
                $attendance = StudentAttendance::create([
                    'attendable_id' => $student->id,
                    'attendable_type' => Student::class,
                    'date' => $validatedData['date'],
                    'status' => $arabicStatus,
                    'period' => $validatedData['period'] ?? null,
                    'notes' => $validatedData['notes'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Attendance record created successfully',
                    'data' => $attendance
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while recording attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store multiple attendance records from frontend
     */
    public function storeBatch(Request $request)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'teacherId' => 'required|integer|exists:teachers,id',
                'date' => 'required|date',
                'time' => 'nullable|string',
                'students' => 'required|array|min:1',
                'students.*.studentId' => 'required|integer|exists:students,id',
                'students.*.status' => 'required|string|in:حاضر,غائب,متأخر,معذور',
                'students.*.notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            // Get teacher to determine period
            $teacher = Teacher::find($validatedData['teacherId']);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            // Determine period based on time or default
            $period = $this->determinePeriodFromTime($validatedData['time'] ?? null);

            // Process each student attendance
            foreach ($validatedData['students'] as $studentData) {
                try {
                    $student = Student::find($studentData['studentId']);
                    if (!$student) {
                        $results[] = [
                            'studentId' => $studentData['studentId'],
                            'success' => false,
                            'message' => 'Student not found'
                        ];
                        $errorCount++;
                        continue;
                    }

                    // Map status from Arabic to English
                    $statusMapping = [
                        'حاضر' => 'present',
                        'غائب' => 'absent',
                        'متأخر' => 'late',
                        'معذور' => 'excused'
                    ];
                    $status = $statusMapping[$studentData['status']] ?? 'absent';

                    // Check if attendance record already exists
                    $existingAttendance = StudentAttendance::where('student_id', $student->id)
                        ->where('date', $validatedData['date'])
                        ->where('period', $period)
                        ->first();

                    if ($existingAttendance) {
                        // Update existing record
                        $existingAttendance->update([
                            'status' => $status,
                            'notes' => $studentData['notes'] ?? null,
                            'recorded_by' => 'API_Frontend'
                        ]);

                        $results[] = [
                            'studentId' => $student->id,
                            'studentName' => $student->name,
                            'success' => true,
                            'action' => 'updated',
                            'data' => $existingAttendance->fresh()
                        ];
                    } else {
                        // Create new attendance record
                        $attendance = StudentAttendance::create([
                            'student_id' => $student->id,
                            'date' => $validatedData['date'],
                            'status' => $status,
                            'period' => $period,
                            'notes' => $studentData['notes'] ?? null,
                            'recorded_by' => 'API_Frontend'
                        ]);

                        $results[] = [
                            'studentId' => $student->id,
                            'studentName' => $student->name,
                            'success' => true,
                            'action' => 'created',
                            'data' => $attendance
                        ];
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $results[] = [
                        'studentId' => $studentData['studentId'],
                        'success' => false,
                        'message' => 'Error processing student: ' . $e->getMessage()
                    ];
                    $errorCount++;
                }
            }

            return response()->json([
                'success' => $errorCount === 0,
                'message' => "تم معالجة {$successCount} طالب بنجاح، {$errorCount} خطأ",
                'summary' => [
                    'total' => count($validatedData['students']),
                    'success' => $successCount,
                    'errors' => $errorCount
                ],
                'results' => $results
            ], $errorCount === 0 ? 200 : 207); // 207 = Multi-Status

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while recording attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance records (optional endpoint for API consumers)
     */
    public function index(Request $request)
    {
        try {
            $query = StudentAttendance::with('student');

            // Filter by teacher_id if provided
            if ($request->has('teacher_id')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->whereHas('quranCircle', function ($circleQuery) use ($request) {
                        // Filter by circles where this teacher is active
                        $circleQuery->whereHas('activeTeachers', function ($teacherQuery) use ($request) {
                            $teacherQuery->where('teachers.id', $request->teacher_id);
                        });
                    });
                });
            }

            // Filter by mosque_id if provided
            if ($request->has('mosque_id')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('mosque_id', $request->mosque_id);
                });
            }

            // Filter by student name if provided
            if ($request->has('student_name')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->student_name . '%');
                });
            }

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $attendances = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $attendances
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching attendance records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance statistics (optional endpoint)
     */
    public function stats(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_name' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = StudentAttendance::query();

            // Filter by student if provided
            if ($request->has('student_name')) {
                $student = Student::where('name', $request->student_name)->first();
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found'
                    ], 404);
                }
                $query->where('student_id', $student->id);
            }

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $stats = [
                'total_records' => $query->count(),
                'present' => (clone $query)->where('status', 'present')->count(),
                'absent' => (clone $query)->where('status', 'absent')->count(),
                'late' => (clone $query)->where('status', 'late')->count(),
                'excused' => (clone $query)->where('status', 'excused')->count(),
            ];

            // Calculate percentages
            if ($stats['total_records'] > 0) {
                $stats['present_percentage'] = round(($stats['present'] / $stats['total_records']) * 100, 2);
                $stats['absent_percentage'] = round(($stats['absent'] / $stats['total_records']) * 100, 2);
                $stats['late_percentage'] = round(($stats['late'] / $stats['total_records']) * 100, 2);
                $stats['excused_percentage'] = round(($stats['excused'] / $stats['total_records']) * 100, 2);
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine period from time string
     */
    private function determinePeriodFromTime($time): string
    {
        if (!$time) {
            return 'العصر'; // Default period
        }

        $hour = (int) substr($time, 0, 2);

        if ($hour >= 3 && $hour < 8) {
            return 'الفجر';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'العصر';
        } elseif ($hour >= 17 && $hour < 20) {
            return 'المغرب';
        } elseif ($hour >= 20 || $hour < 3) {
            return 'العشاء';
        }

        return 'العصر'; // Default
    }
}
