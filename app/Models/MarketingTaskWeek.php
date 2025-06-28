<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasActivityLog;

class MarketingTaskWeek extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog;

    // اسم العرض للنموذج في سجل الأنشطة
    public static $displayName = 'أسبوع مهام تسويقية';
    
    // اسم الوحدة للنموذج في سجل الأنشطة
    public static $moduleName = 'التسويق';

    /**
     * الحقول المستبعدة من تسجيل الأنشطة
     */
    protected $activityExcluded = [
        'updated_at', 
        'created_at',
        'deleted_at',
    ];

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'week_number',
        'year',
        'start_date',
        'end_date',
        'completion_percentage',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completion_percentage' => 'float',
    ];

    /**
     * المستخدم الذي أنشأ الأسبوع
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * المهام المرتبطة بهذا الأسبوع - العلاقة المباشرة الجديدة
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(MarketingTask::class, 'marketing_task_week_id');
    }

    /**
     * المهام المرتبطة بهذا الأسبوع - الطريقة القديمة للتوافق
     */
    public function tasksIndirect(): HasMany
    {
        return $this->hasMany(MarketingTask::class, 'week_number', 'week_number')
                   ->where('year', $this->year);
    }

    /**
     * جميع المهام المرتبطة بهذا الأسبوع (من كلا العلاقتين)
     */
    public function allTasks()
    {
        $directTasks = $this->tasks;
        $indirectTasks = $this->tasksIndirect;
        
        // دمج المجموعتين والتخلص من التكرار
        return $directTasks->concat($indirectTasks)->unique('id');
    }

    /**
     * سجلات إكمال المهام لهذا الأسبوع
     */
    public function taskCompletions(): HasMany
    {
        return $this->hasMany(MarketingTaskCompletion::class, 'week_number', 'week_number')
                    ->where('year', $this->year);
    }

    /**
     * تعيين تواريخ بداية ونهاية الأسبوع بناءً على رقم الأسبوع والسنة
     */
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->start_date || !$model->end_date) {
                $date = Carbon::now();
                if ($model->week_number && $model->year) {
                    $date = Carbon::create($model->year, 1, 1)->startOfYear();
                    $date->setISODate($model->year, $model->week_number);
                }
                
                $model->start_date = $date->startOfWeek();
                $model->end_date = $date->endOfWeek();
            }
            
            if (!$model->title) {
                $model->title = 'أسبوع ' . $model->week_number . ' - ' . $model->year;
            }
        });
    }

    /**
     * حساب نسبة الإنجاز للمهام في هذا الأسبوع
     */
    public function calculateCompletionPercentage(): float
    {
        // الحصول على جميع المهام المرتبطة بهذا الأسبوع (مباشرة وغير مباشرة)
        $tasks = $this->allTasks();
        
        if ($tasks->isEmpty()) {
            return 0;
        }
        
        $totalTasks = $tasks->count();
        $completedTasks = 0;
        
        // الحصول على سجلات الإكمال من النظام الجديد
        $completions = $this->taskCompletions()
                           ->where('is_completed', true)
                           ->get()
                           ->pluck('marketing_task_id')
                           ->toArray();
                           
        // عد المهام المكتملة
        foreach ($tasks as $task) {
            if (in_array($task->id, $completions) || $task->isCompletedForWeek($this->week_number, $this->year)) {
                $completedTasks++;
            }
        }
        
        $percentage = ($completedTasks / $totalTasks) * 100;
        
        // تحديث النسبة في النموذج
        $this->completion_percentage = $percentage;
        $this->save();
        
        return $percentage;
    }

    /**
     * إنشاء أو الحصول على أسبوع المهام بناءً على رقم الأسبوع والسنة
     */
    public static function getOrCreate(int $weekNumber, int $year, int $createdBy = null): self
    {
        $week = self::where('week_number', $weekNumber)
                    ->where('year', $year)
                    ->first();
        
        if (!$week) {
            $week = self::create([
                'week_number' => $weekNumber,
                'year' => $year,
                'status' => 'قيد التنفيذ',
                'created_by' => $createdBy,
            ]);
        }
        
        return $week;
    }

    /**
     * الحصول على الأسبوع الحالي
     */
    public static function current(): self
    {
        $now = Carbon::now();
        return self::getOrCreate($now->weekOfYear, $now->year);
    }

    /**
     * الحصول على الأسبوع السابق
     */
    public static function previous(): self
    {
        $previousWeek = Carbon::now()->subWeek();
        return self::getOrCreate($previousWeek->weekOfYear, $previousWeek->year);
    }

    /**
     * الحصول على الأسبوع القادم
     */
    public static function next(): self
    {
        $nextWeek = Carbon::now()->addWeek();
        return self::getOrCreate($nextWeek->weekOfYear, $nextWeek->year);
    }

    /**
     * ربط المهام الموجودة بهذا الأسبوع التي تستخدم العلاقة القديمة
     */
    public function linkExistingTasks(): int
    {
        $count = 0;
        $tasks = $this->tasksIndirect;
        
        foreach ($tasks as $task) {
            if (!$task->marketing_task_week_id) {
                $task->marketing_task_week_id = $this->id;
                $task->save();
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * ربط جميع المهام الموجودة بالأسابيع المناسبة
     */
    public static function linkAllExistingTasks(): int
    {
        $count = 0;
        $weeks = self::all();
        
        foreach ($weeks as $week) {
            $count += $week->linkExistingTasks();
        }
        
        return $count;
    }

    /**
     * الحصول على وصف النشاط لكل حدث
     */
    public function getActivityDescriptionForEvent(string $event): string
    {
        return match($event) {
            'created' => "تم إنشاء أسبوع مهام تسويقية جديد: {$this->title}",
            'updated' => "تم تحديث أسبوع المهام التسويقية: {$this->title}",
            'deleted' => "تم حذف أسبوع المهام التسويقية: {$this->title}",
            default => parent::getActivityDescriptionForEvent($event),
        };
    }
}
