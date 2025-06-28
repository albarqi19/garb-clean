<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCurriculum extends Model
{
    use HasFactory;
      /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'curriculum_id',
        'curriculum_level_id',
        'teacher_id',
        'start_date',
        'completion_date',
        'status',
        'completion_percentage',
        'notes',
        // حقول المنهج اليومي
        'daily_memorization_pages',
        'daily_minor_review_pages',
        'daily_major_review_pages',
        'current_page',
        'current_surah',
        'current_ayah',
        'last_progress_date',
        'consecutive_days',
        'is_active',
        'daily_goals',
    ];
      /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'completion_percentage' => 'float',
        'last_progress_date' => 'date',
        'is_active' => 'boolean',
        'daily_goals' => 'array',
    ];
    
    /**
     * العلاقة: تقدم المنهج ينتمي إلى طالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    
    /**
     * العلاقة: تقدم المنهج ينتمي إلى منهج
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
    
    /**
     * العلاقة: تقدم المنهج ينتمي إلى مستوى (في حالة منهج الطالب)
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(CurriculumLevel::class, 'curriculum_level_id');
    }
    
    /**
     * العلاقة: تقدم المنهج ينتمي إلى معلم
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
    
    /**
     * العلاقة: تقدم المنهج له تفاصيل تقدم للخطط
     */
    public function progress(): HasMany
    {
        return $this->hasMany(StudentCurriculumProgress::class);
    }
    
    /**
     * نطاق: المناهج قيد التنفيذ فقط
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'قيد التنفيذ');
    }
    
    /**
     * نطاق: المناهج المكتملة فقط
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'مكتمل');
    }
    
    /**
     * تحديث نسبة الإكمال بناءً على تقدم الخطط
     */
    public function updateCompletionPercentage(): void
    {
        $progressItems = $this->progress;
        
        if ($progressItems->isEmpty()) {
            $this->completion_percentage = 0;
        } else {
            $totalProgress = $progressItems->sum('completion_percentage');
            $this->completion_percentage = $totalProgress / $progressItems->count();
            
            // إذا كانت جميع الخطط مكتملة، نعتبر المنهج مكتملاً
            if ($progressItems->where('status', 'مكتمل')->count() === $progressItems->count()) {
                $this->status = 'مكتمل';
                $this->completion_date = now();
            }
        }
          $this->save();
    }
    
    /**
     * الحصول على المنهج اليومي للطالب
     */
    public function getDailyCurriculum(): array
    {
        return [
            'memorization_pages' => $this->daily_memorization_pages ?? 1,
            'minor_review_pages' => $this->daily_minor_review_pages ?? 5,
            'major_review_pages' => $this->daily_major_review_pages ?? 20,
            'current_position' => [
                'page' => $this->current_page,
                'surah' => $this->current_surah,
                'ayah' => $this->current_ayah,
            ],
            'last_progress_date' => $this->last_progress_date,
            'consecutive_days' => $this->consecutive_days,
            'is_active' => $this->is_active,
            'custom_goals' => $this->daily_goals ?? [],
        ];
    }
    
    /**
     * تحديث التقدم اليومي
     */
    public function updateDailyProgress(array $progress): void
    {
        $today = now()->toDateString();
        $lastProgressDate = $this->last_progress_date?->toDateString();
        
        // تحديث الموقع الحالي
        if (isset($progress['current_page'])) {
            $this->current_page = $progress['current_page'];
        }
        if (isset($progress['current_surah'])) {
            $this->current_surah = $progress['current_surah'];
        }
        if (isset($progress['current_ayah'])) {
            $this->current_ayah = $progress['current_ayah'];
        }
        
        // تحديث عدد الأيام المتتالية
        if ($lastProgressDate === $today) {
            // نفس اليوم، لا تغيير في العدد
        } elseif ($lastProgressDate === now()->subDay()->toDateString()) {
            // اليوم التالي، زيادة العدد
            $this->consecutive_days++;
        } else {
            // انقطاع في الأيام، إعادة تعيين العدد
            $this->consecutive_days = 1;
        }
        
        $this->last_progress_date = now();
        $this->save();
    }
    
    /**
     * التحقق من اكتمال المنهج اليومي لليوم
     */
    public function isDailyGoalCompleted(): bool
    {
        return $this->last_progress_date?->isToday() ?? false;
    }
    
    /**
     * نطاق: المناهج اليومية النشطة
     */
    public function scopeActiveDailyPrograms($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * نطاق: المناهج التي لم تكتمل اليوم
     */
    public function scopePendingToday($query)
    {
        return $query->where(function($q) {
            $q->whereNull('last_progress_date')
              ->orWhere('last_progress_date', '<', now()->toDateString());
        });
    }
}
