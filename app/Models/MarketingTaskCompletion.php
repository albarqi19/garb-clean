<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasActivityLog;

class MarketingTaskCompletion extends Model
{
    use HasFactory, HasActivityLog;

    // اسم العرض للنموذج في سجل الأنشطة
    public static $displayName = 'إنجاز مهمة تسويقية';
    
    // اسم الوحدة للنموذج في سجل الأنشطة
    public static $moduleName = 'التسويق';

    /**
     * الحقول المستبعدة من تسجيل الأنشطة
     */
    protected $activityExcluded = [
        'updated_at', 
        'created_at',
    ];

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'marketing_task_id',
        'completed_by',
        'week_number',
        'year',
        'is_completed',
        'notes',
        'completion_date',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'completion_date' => 'datetime',
    ];

    /**
     * المهمة التسويقية المرتبطة بهذا الإنجاز
     */
    public function marketingTask(): BelongsTo
    {
        return $this->belongsTo(MarketingTask::class);
    }

    /**
     * المستخدم الذي أكمل المهمة
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * الأسبوع المرتبط بهذا الإنجاز
     */
    public function marketingTaskWeek()
    {
        return MarketingTaskWeek::where('week_number', $this->week_number)
                                ->where('year', $this->year)
                                ->first();
    }

    /**
     * الحصول على وصف النشاط لكل حدث
     */
    public function getActivityDescriptionForEvent(string $event): string
    {
        $task = $this->marketingTask;
        $taskTitle = $task ? $task->title : 'مهمة محذوفة';
        
        return match($event) {
            'created' => "تم إنجاز المهمة التسويقية: {$taskTitle}",
            'updated' => "تم تعديل إنجاز المهمة التسويقية: {$taskTitle}",
            'deleted' => "تم إلغاء إنجاز المهمة التسويقية: {$taskTitle}",
            default => parent::getActivityDescriptionForEvent($event),
        };
    }
}