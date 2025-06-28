<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryRate extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_title',
        'nationality_type',
        'main_periods_daily_rate',
        'main_periods_monthly_rate',
        'maghrib_daily_rate',
        'maghrib_monthly_rate',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'main_periods_daily_rate' => 'float',
        'main_periods_monthly_rate' => 'float',
        'maghrib_daily_rate' => 'float',
        'maghrib_monthly_rate' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * الحصول على معدل الراتب اليومي لفترة معينة
     *
     * @param string $period الفترة ('الفجر', 'العصر', 'المغرب', 'العشاء')
     * @return float معدل الراتب اليومي
     */
    public function getDailyRateForPeriod($period): float
    {
        if ($period === 'المغرب') {
            return $this->maghrib_daily_rate;
        }
        
        // للفترات الأخرى (الفجر، العصر، العشاء)
        return $this->main_periods_daily_rate;
    }

    /**
     * الحصول على معدل الراتب الشهري لفترة معينة
     *
     * @param string $period الفترة ('الفجر', 'العصر', 'المغرب', 'العشاء')
     * @return float معدل الراتب الشهري
     */
    public function getMonthlyRateForPeriod($period): float
    {
        if ($period === 'المغرب') {
            return $this->maghrib_monthly_rate;
        }
        
        // للفترات الأخرى (الفجر، العصر، العشاء)
        return $this->main_periods_monthly_rate;
    }

    /**
     * الحصول على معدلات الراتب المناسبة بناءً على المسمى الوظيفي والجنسية
     *
     * @param string $jobTitle المسمى الوظيفي
     * @param string $nationalityType نوع الجنسية ('سعودي' أو 'غير سعودي')
     * @return self|null معدلات الراتب المناسبة أو null إذا لم يتم العثور عليها
     */
    public static function findRateByJobAndNationality($jobTitle, $nationalityType): ?self
    {
        return self::where('job_title', $jobTitle)
            ->where('nationality_type', $nationalityType)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();
    }
}
