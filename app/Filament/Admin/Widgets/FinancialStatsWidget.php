<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Revenue;
use App\Models\Expense;
use App\Models\CircleBudget;
use App\Models\CircleIncentive;
use App\Models\FinancialCustody;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // تهيئة المتغيرات الافتراضية
        $currentMonthRevenues = 0;
        $currentMonthExpenses = 0;
        $circlesAtRisk = 0;
        $approvedCustodies = 0;
        $totalIncentives = 0;
        
        try {
            // حساب إجمالي الإيرادات للشهر الحالي
            if (Schema::hasTable('revenues') && Schema::hasColumn('revenues', 'amount') && Schema::hasColumn('revenues', 'date')) {
                $currentMonthRevenues = Revenue::whereMonth('date', Carbon::now()->month)
                    ->whereYear('date', Carbon::now()->year)
                    ->sum('amount');
            }
            
            // حساب إجمالي المصروفات للشهر الحالي
            if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'amount') && Schema::hasColumn('expenses', 'date')) {
                $currentMonthExpenses = Expense::whereMonth('date', Carbon::now()->month)
                    ->whereYear('date', Carbon::now()->year)
                    ->sum('amount');
            }
            
            // حساب عدد الحلقات المعرضة للخطر المالي (أقل من 3 أشهر تغطية)
            if (Schema::hasTable('circle_budgets') && Schema::hasColumn('circle_budgets', 'coverage_months')) {
                $circlesAtRisk = CircleBudget::where('coverage_months', '<', 3)->count();
            }
            
            // حساب إجمالي المبالغ المعتمدة للعهد المالية
            if (Schema::hasTable('financial_custodies')) {
                if (Schema::hasColumn('financial_custodies', 'status') && Schema::hasColumn('financial_custodies', 'amount')) {
                    $approvedCustodies = FinancialCustody::where(function($query) {
                        $query->where('status', 'approved')->orWhere('status', 'disbursed');
                    })->sum('amount');
                }
            }

            // حساب مجموع حوافز الحلقات
            if (Schema::hasTable('circle_incentives') && Schema::hasColumn('circle_incentives', 'amount') && Schema::hasColumn('circle_incentives', 'date')) {
                $totalIncentives = CircleIncentive::whereMonth('date', Carbon::now()->month)
                    ->whereYear('date', Carbon::now()->year)
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            // تجاهل الاستثناءات وعرض قيم افتراضية
        }

        // حساب الفرق بين الإيرادات والمصروفات
        $balance = $currentMonthRevenues - $currentMonthExpenses;
        $balanceColor = $balance >= 0 ? 'success' : 'danger';

        return [
            Stat::make('الإيرادات', number_format($currentMonthRevenues, 2) . ' ريال')
                ->description('إجمالي الإيرادات للشهر الحالي')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('المصروفات', number_format($currentMonthExpenses, 2) . ' ريال')
                ->description('إجمالي المصروفات للشهر الحالي')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('الرصيد', number_format($balance, 2) . ' ريال')
                ->description($balance >= 0 ? 'متبقي في الميزانية' : 'عجز في الميزانية')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-exclamation-circle')
                ->color($balanceColor),
                
            Stat::make('الحلقات المعرضة للخطر', $circlesAtRisk)
                ->description('حلقات بتغطية أقل من 3 أشهر')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}