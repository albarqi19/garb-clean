<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MarketingKpi;
use App\Models\KpiValue;
use App\Models\RevenueTarget;
use App\Models\BranchFollower;
use App\Models\MarketingActivity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MarketingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // تهيئة المتغيرات الافتراضية
        $kpiAchievementRate = 0;
        $revenueAchievementRate = 0;
        $newFollowersCount = 0;
        $conversionRate = 0;
        
        try {
            // حساب نسبة تحقيق مؤشرات الأداء للشهر الحالي
            if (Schema::hasTable('kpi_values')) {
                // نتحقق من وجود العمود
                if (Schema::hasColumn('kpi_values', 'achievement_percentage')) {
                    $kpiAchievementRate = KpiValue::whereMonth('end_date', Carbon::now()->month)
                        ->whereYear('end_date', Carbon::now()->year)
                        ->avg('achievement_percentage');
                } elseif (Schema::hasColumn('kpi_values', 'achievement_rate')) {
                    // استخدام عمود بديل محتمل
                    $kpiAchievementRate = KpiValue::whereMonth('end_date', Carbon::now()->month)
                        ->whereYear('end_date', Carbon::now()->year)
                        ->avg('achievement_rate');
                }

                if (is_null($kpiAchievementRate)) {
                    $kpiAchievementRate = 0;
                }
            }
            
            // حساب نسبة تحقيق أهداف الإيرادات للشهر الحالي
            if (Schema::hasTable('revenue_targets')) {
                if (Schema::hasColumn('revenue_targets', 'achieved_amount') && 
                    Schema::hasColumn('revenue_targets', 'target_amount')) {
                    $revenueTargetsAchievement = RevenueTarget::whereMonth('month', Carbon::now()->month)
                        ->whereYear('year', Carbon::now()->year)
                        ->select(DB::raw('SUM(achieved_amount) / NULLIF(SUM(target_amount), 0) * 100 as achievement_rate'))
                        ->first();
                        
                    $revenueAchievementRate = $revenueTargetsAchievement && $revenueTargetsAchievement->achievement_rate ? 
                        $revenueTargetsAchievement->achievement_rate : 0;
                }
            }
            
            // حساب عدد متابعي الفروع الجدد
            if (Schema::hasTable('branch_followers')) {
                $newFollowersCount = BranchFollower::whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count();
                
                // حساب نسبة تحويل المتابعين إلى متبرعين
                if (Schema::hasColumn('branch_followers', 'converted_to_donor')) {
                    $totalFollowers = BranchFollower::count();
                    
                    if ($totalFollowers > 0) {
                        $conversionRate = (BranchFollower::where('converted_to_donor', true)->count() / $totalFollowers) * 100;
                    }
                }
            }
        } catch (\Exception $e) {
            // تجاهل الاستثناءات وعرض قيم افتراضية
        }

        $kpiColor = $kpiAchievementRate >= 80 ? 'success' : ($kpiAchievementRate >= 50 ? 'warning' : 'danger');
        $revenueColor = $revenueAchievementRate >= 80 ? 'success' : ($revenueAchievementRate >= 50 ? 'warning' : 'danger');

        return [
            Stat::make('تحقيق مؤشرات الأداء', number_format($kpiAchievementRate, 1) . '%')
                ->description('نسبة تحقيق المؤشرات للشهر الحالي')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($kpiColor),

            Stat::make('تحقيق أهداف الإيرادات', number_format($revenueAchievementRate, 1) . '%')
                ->description('نسبة تحقيق الأهداف للشهر الحالي')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($revenueColor),

            Stat::make('متابعون جدد', $newFollowersCount)
                ->description('متابعون مسجلون في الشهر الحالي')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
                
            Stat::make('نسبة التحويل', number_format($conversionRate, 1) . '%')
                ->description('نسبة تحويل المتابعين إلى متبرعين')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}