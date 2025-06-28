<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueTarget extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'revenue_type_id',
        'target_month',
        'month_name',
        'fiscal_year',
        'target_amount',
        'achieved_amount',
        'achievement_percentage',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'target_month' => 'date',
        'fiscal_year' => 'integer',
        'target_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
    ];

    /**
     * علاقة مع نوع الإيراد.
     */
    public function revenueType(): BelongsTo
    {
        return $this->belongsTo(RevenueType::class);
    }

    /**
     * علاقة مع الإيرادات المرتبطة بهذا الهدف.
     */
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class, 'revenue_type_id', 'revenue_type_id')
            ->whereMonth('date', $this->target_month->month)
            ->whereYear('date', $this->target_month->year);
    }

    /**
     * تحديث المبلغ المحقق ونسبة التحقيق.
     */
    public function updateAchievement(): self
    {
        $this->achieved_amount = $this->revenues()->sum('amount');
        
        if ($this->target_amount > 0) {
            $this->achievement_percentage = ($this->achieved_amount / $this->target_amount) * 100;
        } else {
            $this->achievement_percentage = 0;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * الحصول على القيمة المتبقية لتحقيق الهدف.
     */
    public function getRemainingAmountAttribute(): float
    {
        $remaining = $this->target_amount - $this->achieved_amount;
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * الحصول على المتوسط المطلوب تحقيقه يومياً لبقية الشهر.
     */
    public function getDailyTargetAttribute(): ?float
    {
        if ($this->target_month->isPast()) {
            return null;
        }

        $remaining = $this->remaining_amount;
        
        $today = now();
        if ($today->month === $this->target_month->month && $today->year === $this->target_month->year) {
            $daysLeft = $this->target_month->endOfMonth()->diffInDays($today) + 1; // +1 لتضمين اليوم الحالي
        } else {
            $daysLeft = $this->target_month->daysInMonth;
        }
        
        return $daysLeft > 0 ? $remaining / $daysLeft : null;
    }

    /**
     * نطاق الاستعلام للأهداف حسب السنة المالية.
     */
    public function scopeByFiscalYear($query, $year)
    {
        return $query->where('fiscal_year', $year);
    }

    /**
     * نطاق الاستعلام للأهداف حسب نوع الإيراد.
     */
    public function scopeByRevenueType($query, $revenueTypeId)
    {
        return $query->where('revenue_type_id', $revenueTypeId);
    }

    /**
     * نطاق الاستعلام للأهداف المحققة.
     */
    public function scopeAchieved($query)
    {
        return $query->where('achievement_percentage', '>=', 100);
    }

    /**
     * نطاق الاستعلام للأهداف غير المحققة.
     */
    public function scopeNotAchieved($query)
    {
        return $query->where('achievement_percentage', '<', 100);
    }

    /**
     * الخصائص المحسوبة.
     */
    protected $appends = [
        'remaining_amount',
        'daily_target',
    ];
}