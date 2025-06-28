<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasActivityLog;

class MarketingTask extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog;

    // اسم العرض للنموذج في سجل الأنشطة
    public static $displayName = 'مهمة تسويقية';
    
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
        'description',
        'schedule_type',
        'day_of_week',
        'time_of_day',
        'assigned_to',
        'channel',
        'completion_dates', // سيتم الاحتفاظ بهذا الحقل للتوافق مع البيانات السابقة
        'is_recurring',
        'is_active',
        'priority',
        'category',
        'notes',
        'created_by',
        'week_number',
        'year',
        'marketing_task_week_id', // إضافة الحقل الجديد
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completion_dates' => 'array',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * المستخدم المسؤول عن المهمة
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * المستخدم الذي أنشأ المهمة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المستخدم (لاستخدام Filament)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * العلاقة مع أسبوع المهمة (لاستخدام Filament)
     * تقوم هذه الدالة بالتعامل الآمن مع العلاقة
     */
    public function marketingTaskWeek(): BelongsTo
    {
        return $this->belongsTo(MarketingTaskWeek::class, 'marketing_task_week_id');
    }

    /**
     * الحصول على أسبوع المهمة بطريقة آمنة
     */
    public function getMarketingTaskWeek()
    {
        // نحاول أولاً استخدام العلاقة المباشرة الجديدة
        if ($this->marketing_task_week_id) {
            $taskWeek = $this->marketingTaskWeek()->first();
            if ($taskWeek) {
                return $taskWeek;
            }
        }
        
        // نستخدم الطريقة القديمة كبديل
        return MarketingTaskWeek::where('week_number', $this->week_number)
                               ->where('year', $this->year)
                               ->first();
    }

    /**
     * سجلات إكمال هذه المهمة (العلاقة الجديدة)
     */
    public function completions(): HasMany
    {
        return $this->hasMany(MarketingTaskCompletion::class, 'marketing_task_id');
    }

    /**
     * تعيين أسبوع وسنة المهمة بناءً على التاريخ الحالي عند إنشاء المهمة
     */
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->week_number || !$model->year) {
                $now = Carbon::now();
                $model->week_number = $now->weekOfYear;
                $model->year = $now->year;
            }
            
            // إذا لم يتم تعيين الأسبوع المباشر، نحاول الحصول عليه أو إنشاءه
            if (!$model->marketing_task_week_id) {
                $week = MarketingTaskWeek::getOrCreate($model->week_number, $model->year, $model->created_by);
                $model->marketing_task_week_id = $week->id;
            }
        });
    }

    /**
     * تحديث حالة الإنجاز للأسبوع الحالي
     * 
     * @param boolean $completed هل تم إنجاز المهمة؟
     * @param string|null $notes ملاحظات الإنجاز
     * @param Carbon|null $date تاريخ الإنجاز (اختياري)
     * @return bool نجاح العملية
     */
    public function markCompletedForCurrentWeek(bool $completed = true, ?string $notes = null, ?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        $weekNumber = $date->weekOfYear;
        $year = $date->year;
        
        // تحديد المستخدم الذي أكمل المهمة
        $completedById = null;
        try {
            // محاولة الحصول على معرف المستخدم الحالي
            if (app('auth')->hasUser()) {
                $completedById = app('auth')->user()->id;
            }
        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نستخدم المستخدم المسؤول عن المهمة
            $completedById = $this->assigned_to;
        }
        
        // استخدام النظام الجديد - إنشاء أو تحديث سجل الإكمال
        $completion = MarketingTaskCompletion::updateOrCreate(
            [
                'marketing_task_id' => $this->id,
                'week_number' => $weekNumber,
                'year' => $year,
            ],
            [
                'is_completed' => $completed,
                'notes' => $notes,
                'completion_date' => $date,
                'completed_by' => $completedById ?? $this->assigned_to,
            ]
        );
        
        // تحميل بيانات الإنجاز الحالية (للتوافق مع النظام القديم)
        $completionData = $this->completion_dates ?? [];
        
        // تحديث بيانات الإنجاز للأسبوع الحالي
        $completionData["$year-$weekNumber"] = [
            'completed' => $completed,
            'date' => $date->toDateTimeString(),
            'notes' => $notes,
        ];
        
        // حفظ البيانات المحدثة
        $this->completion_dates = $completionData;
        $this->save();
        
        // تحديث نسبة الإكمال في أسبوع المهام (إذا كان موجوداً)
        $taskWeek = $this->taskWeek;
        if ($taskWeek) {
            $taskWeek->calculateCompletionPercentage();
        }
        
        return true;
    }

    /**
     * التحقق مما إذا كانت المهمة قد تم إنجازها في أسبوع معين
     * 
     * @param int|null $weekNumber رقم الأسبوع (اختياري)
     * @param int|null $year السنة (اختياري)
     * @return bool هل تم الإنجاز؟
     */
    public function isCompletedForWeek(?int $weekNumber = null, ?int $year = null): bool
    {
        if ($weekNumber === null || $year === null) {
            $now = Carbon::now();
            $weekNumber = $now->weekOfYear;
            $year = $now->year;
        }
        
        // التحقق من الإنجاز في النظام الجديد أولاً
        $completion = $this->completions()
                          ->where('week_number', $weekNumber)
                          ->where('year', $year)
                          ->first();
                          
        if ($completion) {
            return $completion->is_completed;
        }
        
        // التحقق من الإنجاز في النظام القديم كاحتياط
        $completionData = $this->completion_dates ?? [];
        
        return isset($completionData["$year-$weekNumber"]) && 
               ($completionData["$year-$weekNumber"]['completed'] ?? false);
    }

    /**
     * الحصول على ملاحظات الإنجاز لأسبوع معين
     * 
     * @param int|null $weekNumber رقم الأسبوع (اختياري)
     * @param int|null $year السنة (اختياري)
     * @return string|null ملاحظات الإنجاز
     */
    public function getCompletionNotes(?int $weekNumber = null, ?int $year = null): ?string
    {
        if ($weekNumber === null || $year === null) {
            $now = Carbon::now();
            $weekNumber = $now->weekOfYear;
            $year = $now->year;
        }
        
        // البحث في النظام الجديد أولاً
        $completion = $this->completions()
                          ->where('week_number', $weekNumber)
                          ->where('year', $year)
                          ->first();
                          
        if ($completion) {
            return $completion->notes;
        }
        
        // الرجوع إلى النظام القديم كاحتياط
        $completionData = $this->completion_dates ?? [];
        
        return $completionData["$year-$weekNumber"]['notes'] ?? null;
    }

    /**
     * الحصول على تاريخ إنجاز المهمة لأسبوع معين
     * 
     * @param int|null $weekNumber رقم الأسبوع (اختياري)
     * @param int|null $year السنة (اختياري)
     * @return Carbon|null تاريخ الإنجاز
     */
    public function getCompletionDate(?int $weekNumber = null, ?int $year = null): ?Carbon
    {
        if ($weekNumber === null || $year === null) {
            $now = Carbon::now();
            $weekNumber = $now->weekOfYear;
            $year = $now->year;
        }
        
        // البحث في النظام الجديد أولاً
        $completion = $this->completions()
                          ->where('week_number', $weekNumber)
                          ->where('year', $year)
                          ->first();
                          
        if ($completion) {
            return $completion->completion_date;
        }
        
        // الرجوع إلى النظام القديم كاحتياط
        $completionData = $this->completion_dates ?? [];
        
        if (isset($completionData["$year-$weekNumber"]['date'])) {
            return Carbon::parse($completionData["$year-$weekNumber"]['date']);
        }
        
        return null;
    }

    /**
     * إنشاء نسخة من المهمة للأسبوع التالي
     * 
     * @return self|null نسخة المهمة للأسبوع التالي
     */
    public function createCopyForNextWeek(): ?self
    {
        // هذه العملية تتم فقط للمهام المتكررة النشطة
        if (!$this->is_recurring || !$this->is_active) {
            return null;
        }
        
        // حساب الأسبوع التالي والسنة
        $nextWeekDate = Carbon::now()->addWeek();
        $nextWeek = $nextWeekDate->weekOfYear;
        $nextYear = $nextWeekDate->year;
        
        // التحقق من وجود نسخة للأسبوع التالي
        $exists = self::where('title', $this->title)
            ->where('week_number', $nextWeek)
            ->where('year', $nextYear)
            ->exists();
        
        if ($exists) {
            return null;
        }
        
        // الحصول على أو إنشاء أسبوع المهام التالي
        $nextTaskWeek = MarketingTaskWeek::getOrCreate($nextWeek, $nextYear, $this->created_by);
        
        // إنشاء نسخة جديدة للمهمة للأسبوع التالي
        $copy = $this->replicate(['completion_dates']);
        $copy->week_number = $nextWeek;
        $copy->year = $nextYear;
        $copy->marketing_task_week_id = $nextTaskWeek->id;
        $copy->save();
        
        return $copy;
    }

    /**
     * إنشاء نسخ من جميع المهام المتكررة للأسبوع التالي
     * 
     * @return int عدد المهام التي تم إنشاؤها
     */
    public static function createRecurringTasksForNextWeek(): int
    {
        $count = 0;
        $currentWeekTasks = self::where('is_recurring', true)
            ->where('is_active', true)
            ->get();
        
        foreach ($currentWeekTasks as $task) {
            $newTask = $task->createCopyForNextWeek();
            if ($newTask) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * ترحيل بيانات الإنجاز من نظام البيانات القديم (JSON) إلى النظام الجديد
     * 
     * @return int عدد السجلات التي تم ترحيلها
     */
    public function migrateCompletionData(): int
    {
        $count = 0;
        $completionData = $this->completion_dates ?? [];
        
        if (empty($completionData)) {
            return $count;
        }
        
        foreach ($completionData as $key => $data) {
            if (isset($data['completed']) && isset($data['date'])) {
                [$year, $weekNumber] = explode('-', $key);
                
                // تجاهل السجل إذا كان موجوداً بالفعل
                $exists = MarketingTaskCompletion::where('marketing_task_id', $this->id)
                    ->where('week_number', $weekNumber)
                    ->where('year', $year)
                    ->exists();
                
                if (!$exists) {
                    MarketingTaskCompletion::create([
                        'marketing_task_id' => $this->id,
                        'week_number' => $weekNumber,
                        'year' => $year,
                        'is_completed' => $data['completed'],
                        'notes' => $data['notes'] ?? null,
                        'completion_date' => Carbon::parse($data['date']),
                        'completed_by' => $this->assigned_to,
                    ]);
                    
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * ترحيل بيانات الإنجاز لجميع المهام من النظام القديم إلى النظام الجديد
     * 
     * @return int عدد السجلات التي تم ترحيلها
     */
    public static function migrateAllCompletionData(): int
    {
        $count = 0;
        $tasks = self::whereNotNull('completion_dates')->get();
        
        foreach ($tasks as $task) {
            $count += $task->migrateCompletionData();
        }
        
        return $count;
    }

    /**
     * الإستعلامات المعرفة مسبقاً - مهام الأسبوع الحالي
     */
    public function scopeCurrentWeek($query)
    {
        $now = Carbon::now();
        return $query->where('week_number', $now->weekOfYear)
                    ->where('year', $now->year);
    }

    /**
     * الإستعلامات المعرفة مسبقاً - مهام أسبوع محدد
     */
    public function scopeForWeek($query, int $weekNumber, int $year)
    {
        return $query->where('week_number', $weekNumber)
                    ->where('year', $year);
    }

    /**
     * الإستعلامات المعرفة مسبقاً - المهام النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الإستعلامات المعرفة مسبقاً - المهام حسب المسؤول
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * الإستعلامات المعرفة مسبقاً - المهام حسب جدول محدد
     */
    public function scopeByScheduleType($query, string $scheduleType)
    {
        return $query->where('schedule_type', $scheduleType);
    }
        
    /**
     * الإستعلامات المعرفة مسبقاً - المهام حسب يوم الأسبوع
     */
    public function scopeByDayOfWeek($query, string $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * الإستعلامات المعرفة مسبقاً - المهام المكتملة في الأسبوع الحالي
     */
    public function scopeCompletedThisWeek($query)
    {
        $now = Carbon::now();
        $weekKey = $now->year . '-' . $now->weekOfYear;
        
        return $query->whereRaw("JSON_EXTRACT(completion_dates, '$.$weekKey.completed') = true");
    }

    /**
     * الإستعلامات المعرفة مسبقاً - المهام غير المكتملة في الأسبوع الحالي
     */
    public function scopeNotCompletedThisWeek($query)
    {
        $now = Carbon::now();
        $weekKey = $now->year . '-' . $now->weekOfYear;
        
        return $query->whereRaw("JSON_EXTRACT(completion_dates, '$.$weekKey') IS NULL OR JSON_EXTRACT(completion_dates, '$.$weekKey.completed') = false");
    }

    /**
     * إنشاء المهام الأساسية الافتراضية للأسبوع
     * 
     * @param int $weekNumber رقم الأسبوع
     * @param int $year السنة
     * @param int $userId معرف المستخدم
     * @return array المهام التي تم إنشاؤها
     */
    public static function createDefaultTasks(int $weekNumber, int $year, int $userId): array
    {
        $defaultTasks = [
            [
                'title' => 'إرسال تقرير الإحصائيات الأسبوعي',
                'description' => 'إعداد وإرسال تقرير الإحصائيات الأسبوعي للإدارة',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الخميس',
                'time_of_day' => 'صباحاً',
                'channel' => 'إيميل',
                'priority' => 'عالية',
                'is_recurring' => true,
            ],
            [
                'title' => 'مراجعة التعليقات والرسائل',
                'description' => 'مراجعة التعليقات والرسائل الواردة على منصات التواصل الاجتماعي والرد عليها',
                'schedule_type' => 'يومي',
                'time_of_day' => 'صباحاً',
                'channel' => 'منصات التواصل',
                'priority' => 'عادية',
                'is_recurring' => true,
            ],
            [
                'title' => 'نشر المحتوى الأسبوعي',
                'description' => 'نشر المحتوى الأسبوعي المجدول على منصات التواصل الاجتماعي',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الاثنين',
                'time_of_day' => 'صباحاً',
                'channel' => 'منصات التواصل',
                'priority' => 'عالية',
                'is_recurring' => true,
            ],
            [
                'title' => 'متابعة حملة التبرعات الشهرية',
                'description' => 'متابعة أداء حملة التبرعات الشهرية وتحديث التقارير',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الثلاثاء',
                'time_of_day' => 'مساءً',
                'channel' => 'تقارير',
                'priority' => 'عالية',
                'is_recurring' => true,
            ],
            [
                'title' => 'إعداد خطة المحتوى للأسبوع القادم',
                'description' => 'إعداد وتجهيز خطة المحتوى للأسبوع القادم',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الأربعاء',
                'time_of_day' => 'صباحاً',
                'channel' => 'تخطيط',
                'priority' => 'عادية',
                'is_recurring' => true,
            ],
            [
                'title' => 'تحديث إحصائيات مؤشرات الأداء',
                'description' => 'تحديث إحصائيات مؤشرات الأداء للأسبوع الحالي',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الخميس',
                'time_of_day' => 'مساءً',
                'channel' => 'تقارير',
                'priority' => 'عالية',
                'is_recurring' => true,
            ],
            [
                'title' => 'الإجتماع الأسبوعي لفريق التسويق',
                'description' => 'الإجتماع الأسبوعي لمناقشة المستجدات وخطط الأسبوع القادم',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الأحد',
                'time_of_day' => 'صباحاً',
                'channel' => 'اجتماع',
                'priority' => 'عالية',
                'is_recurring' => true,
            ],
            [
                'title' => 'تحديث روابط المتجر الإلكتروني',
                'description' => 'تحديث روابط المتجر الإلكتروني في المنشورات المجدولة',
                'schedule_type' => 'أسبوعي',
                'day_of_week' => 'الاثنين',
                'time_of_day' => 'مساءً',
                'channel' => 'منصات التواصل',
                'priority' => 'عادية',
                'is_recurring' => true,
            ],
        ];
        
        $createdTasks = [];
        
        foreach ($defaultTasks as $taskData) {
            $task = new self();
            
            foreach ($taskData as $key => $value) {
                $task->{$key} = $value;
            }
            
            $task->week_number = $weekNumber;
            $task->year = $year;
            $task->created_by = $userId;
            $task->assigned_to = $userId;
            $task->is_active = true;
            $task->category = 'marketing';
            
            $task->save();
            $createdTasks[] = $task;
        }
        
        return $createdTasks;
    }

    /**
     * الحصول على وصف النشاط لكل حدث
     */
    public function getActivityDescriptionForEvent(string $event): string
    {
        return match($event) {
            'created' => "تم إنشاء مهمة تسويقية جديدة: {$this->title}",
            'updated' => "تم تعديل المهمة التسويقية: {$this->title}",
            'deleted' => "تم حذف المهمة التسويقية: {$this->title}",
            default => parent::getActivityDescriptionForEvent($event),
        };
    }
}
