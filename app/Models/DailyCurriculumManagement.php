<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCurriculumManagement extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     */
    protected $table = 'student_curricula';

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'curriculum_id',
        'start_date',
        'current_page',
        'current_surah',
        'current_ayah',
        'daily_memorization_pages',
        'daily_minor_review_pages',
        'daily_major_review_pages',
        'status',
        'is_active',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'current_page' => 'integer',
        'current_ayah' => 'integer',
        'daily_memorization_pages' => 'decimal:1',
        'daily_minor_review_pages' => 'integer',
        'daily_major_review_pages' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة: المنهج اليومي ينتمي إلى طالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * العلاقة: المنهج اليومي ينتمي إلى منهج
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * حساب نسبة التقدم في المنهج
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalPages = 604; // إجمالي صفحات المصحف
        $currentPage = $this->current_page ?? 1;
        return round(($currentPage / $totalPages) * 100, 1);
    }

    /**
     * المناهج النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * المناهج قيد التنفيذ
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'قيد التنفيذ');
    }
}
