<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiValue extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'kpi_id',
        'period_start_date',
        'period_end_date',
        'period_label',
        'actual_value',
        'target_value',
        'achievement_percentage',
        'notes',
        'user_id',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'actual_value' => 'float',
        'target_value' => 'float',
        'achievement_percentage' => 'float',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // حساب نسبة تحقيق المستهدف تلقائياً
        static::saving(function ($value) {
            if ($value->target_value > 0) {
                $value->achievement_percentage = ($value->actual_value / $value->target_value) * 100;
            } else {
                $value->achievement_percentage = 0;
            }
        });
    }

    /**
     * علاقة مع مؤشر الأداء.
     */
    public function kpi()
    {
        return $this->belongsTo(MarketingKpi::class, 'kpi_id');
    }

    /**
     * علاقة مع المستخدم الذي سجل القيمة.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * نطاق الاستعلام للقيم حسب الفترة.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_end_date', [$startDate, $endDate]);
    }

    /**
     * نطاق الاستعلام للقيم حسب الشهر والسنة.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('period_end_date', $month)
                     ->whereYear('period_end_date', $year);
    }

    /**
     * نطاق الاستعلام للقيم حسب السنة.
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('period_end_date', $year);
    }

    /**
     * نطاق الاستعلام للقيم التي تحقق المستهدف أو تتجاوزه.
     */
    public function scopeAchievedTarget($query)
    {
        return $query->where('achievement_percentage', '>=', 100);
    }

    /**
     * نطاق الاستعلام للقيم التي لم تحقق المستهدف.
     */
    public function scopeBelowTarget($query)
    {
        return $query->where('achievement_percentage', '<', 100);
    }
}