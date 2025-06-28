<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'due_date',
        'priority',
        'status',
        'department',
        'created_by',
        'assigned_to',
        'completed_at',
        'taskable_id',
        'taskable_type',
        'completion_percentage',
        'tags',
        'is_recurring',
        'recurring_pattern',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'date',
        'is_recurring' => 'boolean',
        'recurring_pattern' => 'json',
    ];

    /**
     * العلاقة مع المستخدم الذي أنشأ المهمة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المستخدم المسند إليه المهمة
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * العلاقة مع تعليقات المهمة
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * العلاقة مع مرفقات المهمة
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * العلاقة مع تاريخ حالات المهمة
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatus::class);
    }

    /**
     * العلاقة المتعددة الأشكال (يمكن ربط المهمة بأي نوع من العناصر)
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * إضافة تعليق جديد على المهمة
     *
     * @param string $content محتوى التعليق
     * @param bool $isInternal هل التعليق داخلي
     * @param bool $isActionItem هل التعليق يشير إلى إجراء مطلوب
     * @return TaskComment
     */
    public function addComment($content, $isInternal = false, $isActionItem = false): TaskComment
    {
        return $this->comments()->create([
            'user_id' => Auth::id(),
            'content' => $content,
            'is_internal' => $isInternal,
            'is_action_item' => $isActionItem,
        ]);
    }

    /**
     * تغيير حالة المهمة وتسجيل التغيير في التاريخ
     *
     * @param string $newStatus الحالة الجديدة
     * @param string|null $comment تعليق/سبب التغيير
     * @param int|null $completionPercentage نسبة الإنجاز الجديدة
     * @return TaskStatus
     */
    public function changeStatus($newStatus, $comment = null, $completionPercentage = null): TaskStatus
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        if ($completionPercentage !== null) {
            $this->completion_percentage = $completionPercentage;
        }
        
        if ($newStatus === 'مكتملة') {
            $this->completion_percentage = 100;
            $this->completed_at = now();
        }
        
        $this->save();
        
        return $this->statusHistory()->create([
            'user_id' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'comment' => $comment,
            'completion_percentage' => $completionPercentage,
        ]);
    }

    /**
     * تحديث نسبة الإنجاز للمهمة
     *
     * @param int $percentage النسبة المئوية الجديدة للإنجاز
     * @return self
     */
    public function updateCompletionPercentage($percentage): self
    {
        $this->completion_percentage = $percentage;
        $this->save();
        
        return $this;
    }

    /**
     * إسناد المهمة لمستخدم آخر
     *
     * @param int $userId معرف المستخدم الجديد
     * @return self
     */
    public function assignTo($userId): self
    {
        $this->assigned_to = $userId;
        $this->save();
        
        return $this;
    }

    /**
     * المهام المتأخرة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام القادمة للأسبوع الحالي
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays(7)])
                     ->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام حسب القسم
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $department القسم
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}
