<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumPlanDay extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'curriculum_plan_id',
        'day_number',
        'memorization_enabled',
        'memorization_from_surah',
        'memorization_from_verse',
        'memorization_to_surah',
        'memorization_to_verse',
        'minor_review_enabled',
        'minor_review_from_surah',
        'minor_review_from_verse',
        'minor_review_to_surah',
        'minor_review_to_verse',
        'major_review_enabled',
        'major_review_from_surah',
        'major_review_from_verse',
        'major_review_to_surah',
        'major_review_to_verse',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'memorization_enabled' => 'boolean',
        'minor_review_enabled' => 'boolean',
        'major_review_enabled' => 'boolean',
        'memorization_from_verse' => 'integer',
        'memorization_to_verse' => 'integer',
        'minor_review_from_verse' => 'integer',
        'minor_review_to_verse' => 'integer',
        'major_review_from_verse' => 'integer',
        'major_review_to_verse' => 'integer',
    ];

    /**
     * خطة المنهج التي ينتمي إليها هذا اليوم
     */
    public function curriculumPlan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class);
    }

    /**
     * الحصول على نص وصفي للحفظ
     */
    public function getMemorizationDescriptionAttribute(): string
    {
        if (!$this->memorization_enabled) {
            return 'لا يوجد حفظ';
        }

        $from = $this->memorization_from_surah;
        $to = $this->memorization_to_surah ?: $this->memorization_from_surah;

        if ($from === $to) {
            return "سورة {$from}: من آية {$this->memorization_from_verse} إلى آية {$this->memorization_to_verse}";
        } else {
            return "من سورة {$from} آية {$this->memorization_from_verse} إلى سورة {$to} آية {$this->memorization_to_verse}";
        }
    }

    /**
     * الحصول على نص وصفي للمراجعة الصغرى
     */
    public function getMinorReviewDescriptionAttribute(): string
    {
        if (!$this->minor_review_enabled) {
            return 'لا توجد مراجعة صغرى';
        }

        $from = $this->minor_review_from_surah;
        $to = $this->minor_review_to_surah ?: $this->minor_review_from_surah;

        if ($from === $to) {
            return "سورة {$from}: من آية {$this->minor_review_from_verse} إلى آية {$this->minor_review_to_verse}";
        } else {
            return "من سورة {$from} آية {$this->minor_review_from_verse} إلى سورة {$to} آية {$this->minor_review_to_verse}";
        }
    }

    /**
     * الحصول على نص وصفي للمراجعة الكبرى
     */
    public function getMajorReviewDescriptionAttribute(): string
    {
        if (!$this->major_review_enabled) {
            return 'لا توجد مراجعة كبرى';
        }

        $from = $this->major_review_from_surah;
        $to = $this->major_review_to_surah ?: $this->major_review_from_surah;

        if ($from === $to) {
            return "سورة {$from}: من آية {$this->major_review_from_verse} إلى آية {$this->major_review_to_verse}";
        } else {
            return "من سورة {$from} آية {$this->major_review_from_verse} إلى سورة {$to} آية {$this->major_review_to_verse}";
        }
    }

    /**
     * الحصول على جميع الأنشطة لهذا اليوم (الحفظ والمراجعات)
     */
    public function getAllActivitiesAttribute(): array
    {
        $activities = [];

        if ($this->memorization_enabled) {
            $activities['memorization'] = $this->memorization_description;
        }

        if ($this->minor_review_enabled) {
            $activities['minor_review'] = $this->minor_review_description;
        }

        if ($this->major_review_enabled) {
            $activities['major_review'] = $this->major_review_description;
        }

        return $activities;
    }
}
