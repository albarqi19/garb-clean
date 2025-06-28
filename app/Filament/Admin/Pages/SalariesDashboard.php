<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\BonusesAndIncentivesWidget;
use App\Filament\Admin\Widgets\SalariesDistributionByMosqueWidget;
use App\Filament\Admin\Widgets\SalariesYearlyTrendWidget;
use App\Filament\Admin\Widgets\TotalMonthlySalariesWidget;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SalariesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.admin.pages.salaries-dashboard';
    
    protected static ?string $navigationLabel = 'الرواتب والمالية';
    
    protected static ?string $title = 'لوحة معلومات الرواتب والمالية';
    
    protected static ?int $navigationSort = 3; // ترتيب الصفحة في القائمة
    
    protected static ?string $navigationGroup = 'لوحة المعلومات';

    public function getHeading(): string|Htmlable
    {
        return 'لوحة معلومات الرواتب والمالية';
    }

    public function getHeaderWidgets(): array
    {
        return [
            TotalMonthlySalariesWidget::class,
            SalariesDistributionByMosqueWidget::class,
            SalariesYearlyTrendWidget::class,
            BonusesAndIncentivesWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
