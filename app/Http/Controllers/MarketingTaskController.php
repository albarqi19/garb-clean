<?php

namespace App\Http\Controllers;

use App\Models\MarketingTask;
use App\Models\MarketingTaskWeek;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MarketingTaskController extends Controller
{
    /**
     * عرض قائمة المهام التسويقية للأسبوع الحالي.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $weekNumber = $request->get('week_number');
        $year = $request->get('year');
        $filterBy = $request->get('filter_by', 'all'); // all, completed, not_completed
        
        // إذا لم يتم تحديد أسبوع، استخدم الأسبوع الحالي
        if (!$weekNumber || !$year) {
            $now = Carbon::now();
            $weekNumber = $now->weekOfYear;
            $year = $now->year;
        }
        
        $query = MarketingTask::forWeek($weekNumber, $year);
        
        // تطبيق الفلترة
        if ($filterBy == 'completed') {
            // استخدام الدالة المساعدة isCompletedForWeek لكل مهمة
            $tasks = $query->get()->filter(function($task) use ($weekNumber, $year) {
                return $task->isCompletedForWeek($weekNumber, $year);
            });
        } elseif ($filterBy == 'not_completed') {
            $tasks = $query->get()->filter(function($task) use ($weekNumber, $year) {
                return !$task->isCompletedForWeek($weekNumber, $year);
            });
        } else {
            $tasks = $query->get();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'week_number' => $weekNumber,
                'year' => $year,
                'week_info' => MarketingTaskWeek::where('week_number', $weekNumber)
                                               ->where('year', $year)
                                               ->first(),
            ],
        ]);
    }

    /**
     * إنشاء مهمة تسويقية جديدة.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule_type' => 'required|string',
            'day_of_week' => 'nullable|string',
            'time_of_day' => 'nullable|string',
            'channel' => 'nullable|string',
            'priority' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'week_number' => 'nullable|integer',
            'year' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // إذا لم يتم تحديد أسبوع، استخدم الأسبوع الحالي
        if (!$request->week_number || !$request->year) {
            $now = Carbon::now();
            $request->merge([
                'week_number' => $now->weekOfYear,
                'year' => $now->year,
            ]);
        }
        
        // إنشاء المهمة الجديدة
        $task = new MarketingTask($request->all());
        $task->created_by = Auth::id() ?? 1;
        $task->assigned_to = $request->assigned_to ?? Auth::id() ?? 1;
        $task->is_active = true;
        $task->category = $request->category ?? 'marketing';
        $task->save();
        
        // تحديث نسبة إكمال الأسبوع
        $weekModel = MarketingTaskWeek::firstOrCreate([
            'week_number' => $task->week_number,
            'year' => $task->year,
        ], [
            'title' => "المهام التسويقية - الأسبوع {$task->week_number} من {$task->year}",
            'start_date' => Carbon::now()->setISODate($task->year, $task->week_number)->startOfWeek(),
            'end_date' => Carbon::now()->setISODate($task->year, $task->week_number)->endOfWeek(),
            'is_current' => true,
            'created_by' => Auth::id() ?? 1,
        ]);
        
        $weekModel->updateCompletionPercentage();
        
        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المهمة بنجاح',
            'data' => $task,
        ], 201);
    }

    /**
     * عرض بيانات مهمة تسويقية محددة.
     * 
     * @param MarketingTask $marketingTask
     * @return JsonResponse
     */
    public function show(MarketingTask $marketingTask): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $marketingTask,
            'assigned_user' => $marketingTask->assignedUser,
            'creator' => $marketingTask->creator,
            'week_info' => $marketingTask->marketingTaskWeek(),
            'is_completed_this_week' => $marketingTask->isCompletedForWeek(),
            'completion_notes' => $marketingTask->getCompletionNotes(),
        ]);
    }

    /**
     * تعديل بيانات مهمة تسويقية محددة.
     * 
     * @param Request $request
     * @param MarketingTask $marketingTask
     * @return JsonResponse
     */
    public function update(Request $request, MarketingTask $marketingTask): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'schedule_type' => 'nullable|string',
            'day_of_week' => 'nullable|string',
            'time_of_day' => 'nullable|string',
            'channel' => 'nullable|string',
            'priority' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'assigned_to' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // تحديث بيانات المهمة
        $marketingTask->update($request->all());
        
        // تحديث نسبة إكمال الأسبوع
        $weekModel = $marketingTask->marketingTaskWeek();
        if ($weekModel) {
            $weekModel->updateCompletionPercentage();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'تم تعديل المهمة بنجاح',
            'data' => $marketingTask,
        ]);
    }

    /**
     * حذف مهمة تسويقية محددة.
     * 
     * @param MarketingTask $marketingTask
     * @return JsonResponse
     */
    public function destroy(MarketingTask $marketingTask): JsonResponse
    {
        // حفظ معلومات أسبوع المهمة قبل الحذف
        $weekModel = $marketingTask->marketingTaskWeek();
        
        // حذف المهمة
        $marketingTask->delete();
        
        // تحديث نسبة إكمال الأسبوع
        if ($weekModel) {
            $weekModel->updateCompletionPercentage();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'تم حذف المهمة بنجاح',
        ]);
    }

    /**
     * وضع علامة إكمال أو إلغاء الإكمال للمهمة في الأسبوع الحالي.
     * 
     * @param Request $request
     * @param MarketingTask $marketingTask
     * @return JsonResponse
     */
    public function toggleComplete(Request $request, MarketingTask $marketingTask): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'completed' => 'required|boolean',
            'notes' => 'nullable|string',
            'week_number' => 'nullable|integer',
            'year' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // إذا لم يتم تحديد أسبوع، استخدم الأسبوع الحالي
        if ($request->week_number && $request->year) {
            $date = Carbon::now()->setISODate($request->year, $request->week_number);
        } else {
            $date = Carbon::now();
        }
        
        // تعديل حالة الإكمال للمهمة
        $marketingTask->markCompletedForCurrentWeek(
            $request->completed,
            $request->notes,
            $date
        );
        
        // تحديث نسبة إكمال الأسبوع
        $weekModel = $marketingTask->marketingTaskWeek();
        if ($weekModel) {
            $weekModel->updateCompletionPercentage();
        }
        
        return response()->json([
            'success' => true,
            'message' => $request->completed ? 'تم وضع علامة اكتمال المهمة بنجاح' : 'تم إلغاء علامة اكتمال المهمة بنجاح',
            'data' => [
                'task' => $marketingTask,
                'is_completed' => $marketingTask->isCompletedForWeek($date->weekOfYear, $date->year),
                'week_info' => $weekModel,
            ],
        ]);
    }

    /**
     * إنشاء أسبوع جديد مع نسخ المهام المتكررة من الأسبوع السابق.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createNextWeek(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_template' => 'nullable|boolean',
            'template_week_id' => 'nullable|integer|required_if:from_template,true',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // حساب الأسبوع التالي
        $nextWeekDate = Carbon::now()->addWeek();
        $nextWeek = $nextWeekDate->weekOfYear;
        $nextYear = $nextWeekDate->year;
        
        // التحقق من وجود أسبوع سابق للأسبوع التالي
        $existingWeek = MarketingTaskWeek::where('week_number', $nextWeek)
                                         ->where('year', $nextYear)
                                         ->first();
        
        if ($existingWeek) {
            return response()->json([
                'success' => false,
                'message' => 'الأسبوع التالي موجود بالفعل',
                'data' => $existingWeek,
            ], 422);
        }
        
        // إنشاء الأسبوع التالي
        $weekModel = MarketingTaskWeek::createForWeek($nextWeek, $nextYear, Auth::id() ?? 1);
        
        // استيراد المهام
        $fromTemplate = $request->from_template ?? false;
        $templateWeekId = $request->template_week_id ?? null;
        
        $importedTasks = $weekModel->importTasksFromPreviousWeek($fromTemplate, $templateWeekId);
        
        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الأسبوع التالي بنجاح وتم استيراد ' . count($importedTasks) . ' مهام',
            'data' => [
                'week' => $weekModel,
                'imported_tasks_count' => count($importedTasks),
            ],
        ], 201);
    }

    /**
     * إنشاء المهام الافتراضية للأسبوع.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createDefaultTasks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'week_number' => 'nullable|integer',
            'year' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // إذا لم يتم تحديد أسبوع، استخدم الأسبوع الحالي
        if (!$request->week_number || !$request->year) {
            $now = Carbon::now();
            $request->merge([
                'week_number' => $now->weekOfYear,
                'year' => $now->year,
            ]);
        }
        
        // إنشاء المهام الافتراضية
        $tasks = MarketingTask::createDefaultTasks(
            $request->week_number,
            $request->year,
            Auth::id() ?? 1
        );
        
        // التأكد من وجود أسبوع وتحديث نسبة الإكمال
        $weekModel = MarketingTaskWeek::firstOrCreate([
            'week_number' => $request->week_number,
            'year' => $request->year,
        ], [
            'title' => "المهام التسويقية - الأسبوع {$request->week_number} من {$request->year}",
            'start_date' => Carbon::now()->setISODate($request->year, $request->week_number)->startOfWeek(),
            'end_date' => Carbon::now()->setISODate($request->year, $request->week_number)->endOfWeek(),
            'is_current' => true,
            'created_by' => Auth::id() ?? 1,
        ]);
        
        $weekModel->updateCompletionPercentage();
        
        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المهام الافتراضية بنجاح',
            'data' => [
                'tasks' => $tasks,
                'week_info' => $weekModel,
            ],
        ], 201);
    }

    /**
     * الحصول على إحصائيات المهام التسويقية.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $weekNumber = $request->get('week_number');
        $year = $request->get('year');
        
        // إذا لم يتم تحديد أسبوع، استخدم الأسبوع الحالي
        if (!$weekNumber || !$year) {
            $now = Carbon::now();
            $weekNumber = $now->weekOfYear;
            $year = $now->year;
        }
        
        $tasks = MarketingTask::forWeek($weekNumber, $year)->get();
        
        // إحصائيات حسب الإكمال
        $completedTasks = $tasks->filter(function($task) use ($weekNumber, $year) {
            return $task->isCompletedForWeek($weekNumber, $year);
        });
        
        $pendingTasks = $tasks->filter(function($task) use ($weekNumber, $year) {
            return !$task->isCompletedForWeek($weekNumber, $year);
        });
        
        // إحصائيات حسب الأولوية
        $highPriorityTasks = $tasks->where('priority', 'عالية');
        $normalPriorityTasks = $tasks->where('priority', 'عادية');
        $lowPriorityTasks = $tasks->where('priority', 'منخفضة');
        
        // إحصائيات حسب نوع الجدولة
        $dailyTasks = $tasks->where('schedule_type', 'يومي');
        $weeklyTasks = $tasks->where('schedule_type', 'أسبوعي');
        
        // إحصائيات حسب القناة
        $channelsStats = $tasks->groupBy('channel')->map->count()->toArray();
        
        return response()->json([
            'success' => true,
            'data' => [
                'week_number' => $weekNumber,
                'year' => $year,
                'total_tasks' => $tasks->count(),
                'completion' => [
                    'completed' => $completedTasks->count(),
                    'pending' => $pendingTasks->count(),
                    'completion_ratio' => $tasks->count() > 0 ? 
                        round(($completedTasks->count() / $tasks->count()) * 100, 2) : 0,
                ],
                'priority' => [
                    'high' => $highPriorityTasks->count(),
                    'normal' => $normalPriorityTasks->count(),
                    'low' => $lowPriorityTasks->count(),
                ],
                'schedule_type' => [
                    'daily' => $dailyTasks->count(),
                    'weekly' => $weeklyTasks->count(),
                ],
                'channels' => $channelsStats,
                'week_info' => MarketingTaskWeek::where('week_number', $weekNumber)
                                               ->where('year', $year)
                                               ->first(),
            ],
        ]);
    }
}
