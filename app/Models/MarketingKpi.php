<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingKpi extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'unit',
        'frequency',
        'calculation_type',
        'weight',
        'target_value',
        'is_active',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'weight' => 'float',
        'target_value' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * علاقة مع قيم المؤشر.
     */
    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class, 'kpi_id');
    }

    /**
     * الحصول على آخر قيمة للمؤشر.
     */
    public function getLatestValueAttribute()
    {
        return $this->values()->latest('period_end_date')->first();
    }

    /**
     * الحصول على متوسط قيم المؤشر للعام الحالي.
     */
    public function getCurrentYearAverageAttribute(): ?float
    {
        $currentYear = now()->year;
        $values = $this->values()
            ->whereYear('period_end_date', $currentYear)
            ->get();
            
        if ($values->isEmpty()) {
            return null;
        }
        
        return $values->avg('actual_value');
    }

    /**
     * الحصول على إجمالي قيم المؤشر للعام الحالي (للمؤشرات التراكمية).
     */
    public function getCurrentYearSumAttribute(): ?float
    {
        if ($this->calculation_type !== 'تراكمي') {
            return null;
        }
        
        $currentYear = now()->year;
        
        return $this->values()
            ->whereYear('period_end_date', $currentYear)
            ->sum('actual_value');
    }

    /**
     * نطاق الاستعلام للمؤشرات النشطة.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق الاستعلام حسب دورية المؤشر.
     */
    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * الخصائص المحسوبة.
     */
    protected $appends = [
        'latest_value',
        'current_year_average',
        'current_year_sum',
    ];
}