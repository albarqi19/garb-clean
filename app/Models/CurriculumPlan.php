<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumPlan extends Model
{
    use HasFactory;
      /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */    protected $fillable = [
        'curriculum_id',
        'curriculum_level_id',
        'name',
        'plan_type',
        'content',
        'instructions',
        'expected_days',
        'is_active',
        'surah_number',
        'start_verse',
        'end_verse',
        'calculated_verses',
        'content_type',
        'formatted_content',
        // Multi-surah support fields
        'range_type',
        'start_surah_number',
        'end_surah_number',
        'start_surah_verse',
        'end_surah_verse',
        'total_verses_calculated',
        'multi_surah_formatted_content',
        
        // الحقول الجديدة لمنشئ المناهج
        'description',
        'type',
        'period',
        'total_days',
        'plan_data',
        'created_by',
    ];
      /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة.
     *
     * @var array<string, string>
     */    protected $casts = [
        'expected_days' => 'integer',
        'is_active' => 'boolean',
        'surah_number' => 'integer',
        'start_verse' => 'integer',
        'end_verse' => 'integer',
        'calculated_verses' => 'integer',
        // Multi-surah support casts
        'start_surah_number' => 'integer',
        'end_surah_number' => 'integer',
        'start_surah_verse' => 'integer',
        'end_surah_verse' => 'integer',
        'total_verses_calculated' => 'integer',
        
        // الحقول الجديدة لمنشئ المناهج
        'total_days' => 'integer',
        'plan_data' => 'array',
    ];

    /**
     * القيم الافتراضية للخصائص
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'content_type' => 'text',
        'range_type' => 'single_surah',
        'is_active' => true,
    ];
    
    /**
     * العلاقة: الخطة تنتمي إلى منهج
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
    
    /**
     * العلاقة: الخطة تنتمي إلى مستوى (اختياري)
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(CurriculumLevel::class, 'curriculum_level_id');
    }
      /**
     * العلاقة: الخطة لها العديد من بيانات تقدم الطلاب
     */
    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }
    
    /**
     * العلاقة: الخطة لها العديد من أيام الخطة (للخطط المنشأة من منشئ المناهج)
     */
    public function planDays(): HasMany
    {
        return $this->hasMany(CurriculumPlanDay::class);
    }
    
    /**
     * العلاقة: الخطة تنتمي لمنشئ الخطة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * نطاق: الخطط النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق: الخطط ذات النطاق الواحد فقط
     */
    public function scopeSingleSurah($query)
    {
        return $query->where('range_type', 'single_surah');
    }

    /**
     * نطاق: الخطط ذات النطاق المتعدد فقط
     */
    public function scopeMultiSurah($query)
    {
        return $query->where('range_type', 'multi_surah');
    }

    /**
     * نطاق: الخطط القرآنية فقط
     */
    public function scopeQuranContent($query)
    {
        return $query->where('content_type', 'quran');
    }
    
    /**
     * نطاق: خطط الدروس فقط
     */
    public function scopeLessons($query)
    {
        return $query->where('plan_type', 'الدرس');
    }
    
    /**
     * نطاق: خطط المراجعة الصغرى فقط
     */
    public function scopeMinorReviews($query)
    {
        return $query->where('plan_type', 'المراجعة الصغرى');
    }
      /**
     * نطاق: خطط المراجعة الكبرى فقط
     */
    public function scopeMajorReviews($query)
    {
        return $query->where('plan_type', 'المراجعة الكبرى');
    }    /**
     * حساب عدد الآيات تلقائياً عند حفظ النموذج
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($plan) {
            if ($plan->content_type === 'quran') {
                $quranService = app(\App\Services\QuranService::class);
                
                // Handle multi-surah ranges
                if ($plan->range_type === 'multi_surah' && 
                    $plan->start_surah_number && 
                    $plan->end_surah_number) {
                      $plan->total_verses_calculated = $quranService->calculateMultiSurahVerseCount(
                        $plan->start_surah_number,
                        $plan->start_surah_verse ?? 1,
                        $plan->end_surah_number,
                        $plan->end_surah_verse ?? null
                    );
                      $plan->multi_surah_formatted_content = $quranService->formatMultiSurahContent(
                        $plan->start_surah_number,
                        $plan->start_surah_verse ?? 1,
                        $plan->end_surah_number,
                        $plan->end_surah_verse ?? null
                    );
                    
                    // Fill the content field for multi-surah ranges
                    $plan->content = $plan->multi_surah_formatted_content;
                }
                // Handle single-surah ranges (existing functionality)
                elseif (($plan->range_type === 'single_surah' || !$plan->range_type) && 
                        $plan->surah_number && 
                        $plan->start_verse && 
                        $plan->end_verse) {
                    
                    $plan->calculated_verses = $quranService->calculateVerseCount(
                        $plan->surah_number,
                        $plan->start_verse,
                        $plan->end_verse
                    );
                      $plan->formatted_content = $quranService->formatSurahContent(
                        $plan->surah_number,
                        $plan->start_verse,
                        $plan->end_verse
                    );
                    
                    // Fill the content field for single-surah ranges
                    $plan->content = $plan->formatted_content;
                    
                    // Set range_type if not set
                    if (!$plan->range_type) {
                        $plan->range_type = 'single_surah';
                    }
                }
            }
        });
    }    /**
     * الحصول على المحتوى المنسق للعرض
     */
    public function getDisplayContentAttribute(): string
    {
        if ($this->content_type === 'quran') {
            if ($this->range_type === 'multi_surah' && $this->multi_surah_formatted_content) {
                return $this->multi_surah_formatted_content;
            }
            
            if ($this->range_type === 'single_surah' && $this->formatted_content) {
                return $this->formatted_content;
            }
        }
        
        return $this->content ?? '';
    }    /**
     * التحقق من صحة أرقام الآيات
     */
    public function validateVerseNumbers(): bool
    {
        if ($this->content_type !== 'quran') {
            return true;
        }

        // Validate multi-surah ranges
        if ($this->range_type === 'multi_surah') {
            return $this->validateMultiSurahRange();
        }

        // Validate single-surah ranges (existing logic)
        if (!$this->surah_number) {
            return true;
        }

        $totalVerses = \App\Services\QuranService::getVerseCount($this->surah_number);
        return $this->start_verse >= 1 
            && $this->end_verse <= $totalVerses 
            && $this->start_verse <= $this->end_verse;
    }    /**
     * التحقق من صحة نطاق السور المتعددة
     */
    public function validateMultiSurahRange(): bool
    {
        if (!$this->start_surah_number || !$this->end_surah_number) {
            return false;
        }

        $quranService = app(\App\Services\QuranService::class);
        
        return $quranService->validateMultiSurahRange(
            $this->start_surah_number,
            $this->end_surah_number,
            $this->start_surah_verse ?? 1,
            $this->end_surah_verse ?? null
        );
    }

    /**
     * الحصول على إجمالي عدد الآيات (للنطاق الواحد أو المتعدد)
     */
    public function getTotalVersesAttribute(): int
    {
        if ($this->range_type === 'multi_surah') {
            return $this->total_verses_calculated ?? 0;
        }
        
        return $this->calculated_verses ?? 0;
    }

    /**
     * الحصول على ملخص النطاق للسور المتعددة
     */
    public function getMultiSurahSummaryAttribute(): array
    {
        if ($this->range_type !== 'multi_surah' || !$this->start_surah_number || !$this->end_surah_number) {
            return [];
        }

        $quranService = app(\App\Services\QuranService::class);
        
        return $quranService->getMultiSurahRangeSummary(
            $this->start_surah_number,
            $this->end_surah_number,
            $this->start_surah_verse ?? 1,
            $this->end_surah_verse ?? null
        );
    }

    /**
     * التحقق من كون النطاق يشمل سور متعددة
     */
    public function isMultiSurahRange(): bool
    {
        return $this->range_type === 'multi_surah';
    }

    /**
     * التحقق من كون النطاق يشمل سورة واحدة فقط
     */
    public function isSingleSurahRange(): bool
    {
        return $this->range_type === 'single_surah' || (!$this->range_type && $this->surah_number);
    }

    /**
     * السجلات المكتملة لهذه الخطة
     */
    public function completedProgress()
    {
        return $this->studentProgress()->whereIn('status', ['completed', 'reviewed', 'mastered']);
    }

    /**
     * السجلات النشطة لهذه الخطة
     */
    public function activeProgress()
    {
        return $this->studentProgress()->whereIn('status', ['not_started', 'in_progress']);
    }

    /**
     * السجلات التي تحتاج مراجعة لهذه الخطة
     */
    public function progressNeedingReview()
    {
        return $this->studentProgress()->where('status', 'needs_revision');
    }

    /**
     * إحصائيات تقدم الطلاب لهذه الخطة
     */
    public function getProgressStatsAttribute(): array
    {
        $total = $this->studentProgress()->count();
        $completed = $this->completedProgress()->count();
        $inProgress = $this->activeProgress()->count();
        $needsReview = $this->progressNeedingReview()->count();

        return [
            'total_students' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'needs_review' => $needsReview,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'average_score' => $this->studentProgress()->whereNotNull('performance_score')->avg('performance_score'),
        ];
    }

    /**
     * متوسط الوقت المستغرق لإكمال هذه الخطة
     */
    public function getAverageCompletionTimeAttribute(): ?float
    {
        $completedRecords = $this->studentProgress()
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedRecords->isEmpty()) {
            return null;
        }

        $totalMinutes = $completedRecords->sum(function ($record) {
            return $record->started_at->diffInMinutes($record->completed_at);
        });

        return round($totalMinutes / $completedRecords->count(), 1);
    }

    /**
     * معدل نجاح التسميع لهذه الخطة
     */
    public function getRecitationSuccessRateAttribute(): float
    {
        $totalRecitations = $this->studentProgress()
            ->whereNotNull('recitation_status')
            ->count();

        if ($totalRecitations === 0) {
            return 0;
        }

        $successfulRecitations = $this->studentProgress()
            ->whereIn('recitation_status', ['passed', 'excellent'])
            ->count();

        return round(($successfulRecitations / $totalRecitations) * 100, 1);
    }
}
