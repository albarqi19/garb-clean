<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicPlan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الخصائص التي يمكن تعبئتها بشكل جماعي.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    /**
     * الخصائص التي يجب تحويلها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * الحصول على مؤشرات الخطة الاستراتيجية.
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(StrategicIndicator::class);
    }

    /**
     * الحصول على المستخدم الذي أنشأ الخطة.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * حساب نسبة الإنجاز العامة للخطة الاستراتيجية.
     *
     * @param int|null $year السنة المطلوب حساب الإنجاز لها
     * @param string|null $quarter الربع المطلوب (first_quarter, second_quarter, third_quarter, fourth_quarter)
     * @return float نسبة الإنجاز
     */
    public function calculateAchievementPercentage(?int $year = null, ?string $quarter = null): float
    {
        $year = $year ?? date('Y');
        
        $indicators = $this->indicators;
        
        if ($indicators->isEmpty()) {
            return 0;
        }
        
        $totalIndicators = $indicators->count();
        $totalAchievement = 0;
        
        foreach ($indicators as $indicator) {
            if ($quarter) {
                $monitoring = $indicator->monitorings()
                    ->where('year', $year)
                    ->where('period', $quarter)
                    ->first();
                
                if ($monitoring) {
                    $totalAchievement += $monitoring->achievement_percentage;
                }
            } else {
                // حساب متوسط الإنجاز لجميع الأرباع في السنة المحددة
                $monitorings = $indicator->monitorings()
                    ->where('year', $year)
                    ->get();
                
                if ($monitorings->isNotEmpty()) {
                    $totalAchievement += $monitorings->avg('achievement_percentage');
                }
            }
        }
        
        return $totalAchievement / $totalIndicators;
    }
}
