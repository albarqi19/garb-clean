<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleSupervisor extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'supervisor_id',
        'quran_circle_id',
        'assignment_date',
        'end_date',
        'is_active',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'assignment_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // عند إنهاء تعيين مشرف، قم بتعيين تاريخ الإنهاء
        static::updating(function ($assignment) {
            if ($assignment->isDirty('is_active') && $assignment->is_active === false && !$assignment->end_date) {
                $assignment->end_date = now();
            }
        });
    }

    /**
     * علاقة مع المشرف.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * علاقة مع الحلقة القرآنية.
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * علاقة مع زيارات المشرف لهذه الحلقة.
     */
    public function visits()
    {
        return SupervisorVisit::where('supervisor_id', $this->supervisor_id)
                             ->where('quran_circle_id', $this->quran_circle_id);
    }

    /**
     * المهام المرتبطة بمشرف الحلقة
     */
    public function tasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /**
     * المهام النشطة (قيد التنفيذ) لمشرف الحلقة
     */
    public function activeTasks()
    {
        return $this->tasks()->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام المتأخرة لمشرف الحلقة
     */
    public function overdueTasks()
    {
        return $this->tasks()->where('status', 'متأخرة')
                 ->orWhere(function($query) {
                     $query->where('due_date', '<', now())
                           ->whereNotIn('status', ['مكتملة', 'ملغاة']);
                 });
    }

    /**
     * الحصول على عدد زيارات المشرف لهذه الحلقة.
     */
    public function getVisitsCountAttribute(): int
    {
        return $this->visits()->count();
    }

    /**
     * الحصول على عدد زيارات المشرف المكتملة لهذه الحلقة.
     */
    public function getCompletedVisitsCountAttribute(): int
    {
        return $this->visits()->completed()->count();
    }

    /**
     * الحصول على آخر زيارة للمشرف لهذه الحلقة.
     */
    public function getLastVisitAttribute()
    {
        return $this->visits()->orderBy('visit_date', 'desc')->first();
    }

    /**
     * الحصول على متوسط تقييم الحلقة من قبل المشرف.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $completedVisits = $this->visits()->completed()->whereNotNull('circle_rating')->get();
        
        if ($completedVisits->isEmpty()) {
            return null;
        }
        
        return $completedVisits->avg('circle_rating');
    }

    /**
     * نطاق الاستعلام للتعيينات النشطة.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق الاستعلام للتعيينات غير النشطة.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * الخصائص الملحقة.
     */
    protected $appends = [
        'visits_count',
        'completed_visits_count',
        'last_visit',
        'average_rating',
    ];
}