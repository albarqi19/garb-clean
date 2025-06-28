<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quran_circle_id',
        'amount',
        'revenue_type_id',
        'month',
        'revenue_date',
        'academic_term_id',
        'transaction_reference',
        'donor_name',
        'donor_contact',
        'is_for_center',
        'notes',
        'recorded_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'revenue_date' => 'date',
        'is_for_center' => 'boolean',
    ];

    /**
     * الحلقة المرتبطة بالإيراد (إذا كان الإيراد للحلقة)
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * نوع الإيراد
     */
    public function revenueType(): BelongsTo
    {
        return $this->belongsTo(RevenueType::class);
    }

    /**
     * الفصل الدراسي المرتبط بالإيراد
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * الشخص الذي سجل الإيراد
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * الحصول على إجمالي الإيرادات لحلقة معينة في فترة محددة
     *
     * @param int $circleId معرف الحلقة
     * @param string $month الشهر
     * @param int|null $academicTermId معرف الفصل الدراسي (اختياري)
     * @return float
     */
    public static function getTotalForCircle($circleId, $month, $academicTermId = null): float
    {
        $query = self::where('quran_circle_id', $circleId)
            ->where('month', $month);
            
        if ($academicTermId) {
            $query->where('academic_term_id', $academicTermId);
        }
        
        return $query->sum('amount');
    }

    /**
     * الحصول على إجمالي الإيرادات للمركز في فترة محددة
     *
     * @param string $month الشهر
     * @param int|null $academicTermId معرف الفصل الدراسي (اختياري)
     * @return float
     */
    public static function getTotalForCenter($month, $academicTermId = null): float
    {
        $query = self::where('is_for_center', true)
            ->where('month', $month);
            
        if ($academicTermId) {
            $query->where('academic_term_id', $academicTermId);
        }
        
        return $query->sum('amount');
    }
}
