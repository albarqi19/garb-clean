<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuranCircle extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mosque_id',
        'supervisor_id',
        'circle_type',
        'circle_status',
        'time_period',
        'registration_link',
        'has_ratel',
        'has_qias',
        'masser_link',
        'monitor_id',
    ];

    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_ratel' => 'boolean',
        'has_qias' => 'boolean',
    ];

    /**
     * المسجد الذي توجد به هذه الحلقة
     */
    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * المشرف المسؤول عن هذه الحلقة
     * ملاحظة: سيتم تعديل هذا لاحقًا عندما يتم إنشاء نموذج المشرفين
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * المراقب المسؤول عن هذه الحلقة
     * ملاحظة: سيتم تعديل هذا لاحقًا عندما يتم إنشاء نموذج المراقبين
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'monitor_id');
    }

    /**
     * معلم الحلقة الفردية (إذا كانت الحلقة فردية)
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(IndividualCircleTeacher::class, 'circle_id');
    }

    /**
     * تحقق ما إذا كانت الحلقة فردية
     */
    public function getIsIndividualAttribute(): bool
    {
        return $this->circle_type === 'حلقة فردية';
    }

    /**
     * الحلقات الفرعية التابعة للمدرسة القرآنية (إذا كانت حلقة جماعية)
     */
    public function circleGroups(): HasMany
    {
        return $this->hasMany(CircleGroup::class);
    }
    
    /**
     * تحقق ما إذا كانت الحلقة جماعية (مدرسة قرآنية)
     */
    public function getIsGroupCircleAttribute(): bool
    {
        return $this->circle_type === 'حلقة جماعية';
    }
    
    /**
     * المعلمون المرتبطون بهذه الحلقة (العلاقة القديمة - يُحتفظ بها للتوافق مع النسخ السابقة)
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * تكليفات المعلمين لهذه الحلقة
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherCircleAssignment::class);
    }

    /**
     * تكليفات المعلمين النشطة لهذه الحلقة
     */
    public function activeTeacherAssignments(): HasMany
    {
        return $this->teacherAssignments()->active();
    }

    /**
     * المعلمون النشطون في هذه الحلقة (العلاقة الجديدة many-to-many)
     */
    public function activeTeachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_circle_assignments', 'quran_circle_id', 'teacher_id')
            ->withPivot(['is_active', 'start_date', 'end_date', 'notes'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * جميع المعلمين المرتبطين بهذه الحلقة (نشطون وغير نشطين)
     */
    public function allTeachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_circle_assignments', 'quran_circle_id', 'teacher_id')
            ->withPivot(['is_active', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    /**
     * المعلم الأساسي للحلقة (أول معلم نشط)
     */
    public function primaryTeacher()
    {
        return $this->activeTeachers()->orderBy('teacher_circle_assignments.start_date')->first();
    }

    /**
     * التحقق من وجود معلم نشط في الحلقة
     */
    public function hasActiveTeacher(): bool
    {
        return $this->activeTeachers()->exists();
    }

    /**
     * عدد المعلمين النشطين في الحلقة
     */
    public function getActiveTeachersCountAttribute(): int
    {
        return $this->activeTeachers()->count();
    }

    /**
     * الطلاب المرتبطون بهذه الحلقة
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * الحوافز الإضافية المخصصة لهذه الحلقة
     */
    public function incentives(): HasMany
    {
        return $this->hasMany(CircleIncentive::class);
    }
    
    /**
     * المشرفون المعينون على الحلقة
     */
    public function circleSupervisors(): HasMany
    {
        return $this->hasMany(CircleSupervisor::class, 'quran_circle_id');
    }
    
    /**
     * المشرفون النشطون المعينون على الحلقة
     */
    public function activeSupervisors(): HasMany
    {
        return $this->circleSupervisors()->active();
    }
    
    /**
     * زيارات المشرفين لهذه الحلقة
     */
    public function supervisorVisits(): HasMany
    {
        return $this->hasMany(SupervisorVisit::class, 'quran_circle_id');
    }
    
    /**
     * زيارات المشرفين المكتملة لهذه الحلقة
     */
    public function completedSupervisorVisits(): HasMany
    {
        return $this->supervisorVisits()->completed();
    }
    
    /**
     * آخر زيارة إشرافية للحلقة
     */
    public function getLastSupervisorVisitAttribute()
    {
        return $this->supervisorVisits()
            ->orderBy('visit_date', 'desc')
            ->first();
    }
    
    /**
     * متوسط تقييم الحلقة من الزيارات الإشرافية
     */
    public function getAverageRatingAttribute(): ?float
    {
        $completedVisits = $this->completedSupervisorVisits()
            ->whereNotNull('circle_rating')
            ->get();
        
        if ($completedVisits->isEmpty()) {
            return null;
        }
        
        return $completedVisits->avg('circle_rating');
    }
    
    /**
     * التحقق مما إذا كانت الحلقة تستخدم برنامج رتل
     */
    public function ratelActivationStatus(): string
    {
        $latestVisit = $this->lastSupervisorVisit;
        if ($latestVisit && $latestVisit->ratel_activated) {
            return 'مفعّل';
        } elseif ($this->has_ratel) {
            return 'مفعّل';
        }
        
        return 'غير مفعّل';
    }

    /**
     * الحصول على إجمالي الحوافز الإضافية لهذه الحلقة في شهر محدد
     *
     * @param string $month الشهر (مثل "يناير 2025")
     * @return float إجمالي الحوافز
     */
    public function getIncentiveTotalForMonth($month): float
    {
        return $this->incentives()
            ->where('month', $month)
            ->sum('amount');
    }

    /**
     * الحصول على المبلغ المتبقي من الحوافز لهذه الحلقة في شهر محدد
     *
     * @param string $month الشهر (مثل "يناير 2025")
     * @return float المبلغ المتبقي
     */
    public function getRemainingIncentiveForMonth($month): float
    {
        return $this->incentives()
            ->where('month', $month)
            ->sum('remaining_amount');
    }
    
    /**
     * المهام المرتبطة بهذه الحلقة
     */
    public function tasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /**
     * المهام النشطة (قيد التنفيذ) لهذه الحلقة
     */
    public function activeTasks()
    {
        return $this->tasks()->whereNotIn('status', ['مكتملة', 'ملغاة']);
    }

    /**
     * المهام المتأخرة لهذه الحلقة
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
     * الخصائص الملحقة
     */
    protected $appends = [
        'is_individual',
        'is_group_circle',
        'last_supervisor_visit',
        'average_rating',
        'active_teachers_count',
    ];
}
