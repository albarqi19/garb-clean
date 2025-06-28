<?php

namespace App\Http\Controllers;

use App\Models\MarketingTask;
use App\Models\MarketingTaskWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingTaskMigrationController extends Controller
{
    /**
     * عرض واجهة ترحيل البيانات
     */
    public function index()
    {
        $stats = [
            'total_tasks' => MarketingTask::count(),
            'tasks_with_completions' => MarketingTask::whereNotNull('completion_dates')->count(),
            'total_weeks' => MarketingTaskWeek::count(),
        ];
        
        return view('marketing.tasks.migrate', compact('stats'));
    }
    
    /**
     * تنفيذ عملية ترحيل البيانات
     */
    public function migrate(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // 1. ربط المهام الموجودة بالأسابيع المناسبة
            $linkedTasksCount = MarketingTaskWeek::linkAllExistingTasks();
            
            // 2. ترحيل بيانات الإنجاز من النظام القديم إلى النظام الجديد
            $migratedCompletionsCount = MarketingTask::migrateAllCompletionData();
            
            // 3. تحديث نسب الإنجاز في جميع الأسابيع
            $weeks = MarketingTaskWeek::all();
            foreach ($weeks as $week) {
                $week->calculateCompletionPercentage();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم ترحيل البيانات بنجاح',
                'linked_tasks' => $linkedTasksCount,
                'migrated_completions' => $migratedCompletionsCount,
                'updated_weeks' => $weeks->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء ترحيل البيانات: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * إنشاء واجهة CLI لترحيل البيانات
     */
    public function migrateFromCli()
    {
        try {
            DB::beginTransaction();
            
            echo "بدء عملية ترحيل بيانات المهام التسويقية...\n";
            
            echo "1. ربط المهام الموجودة بالأسابيع المناسبة...\n";
            $linkedTasksCount = MarketingTaskWeek::linkAllExistingTasks();
            echo "   تم ربط {$linkedTasksCount} مهمة بالأسابيع المناسبة.\n";
            
            echo "2. ترحيل بيانات الإنجاز من النظام القديم إلى النظام الجديد...\n";
            $migratedCompletionsCount = MarketingTask::migrateAllCompletionData();
            echo "   تم ترحيل {$migratedCompletionsCount} سجل إنجاز.\n";
            
            echo "3. تحديث نسب الإنجاز في جميع الأسابيع...\n";
            $weeks = MarketingTaskWeek::all();
            $updatedWeeks = 0;
            foreach ($weeks as $week) {
                $week->calculateCompletionPercentage();
                $updatedWeeks++;
            }
            echo "   تم تحديث {$updatedWeeks} أسبوع.\n";
            
            DB::commit();
            
            echo "تم ترحيل البيانات بنجاح!\n";
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            
            echo "حدث خطأ أثناء ترحيل البيانات: {$e->getMessage()}\n";
            return 1;
        }
    }
}