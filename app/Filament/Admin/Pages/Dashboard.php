<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\EducationalStatsWidget;
use App\Filament\Admin\Widgets\FinancialStatsWidget;
use App\Filament\Admin\Widgets\MarketingStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = 'الرئيسية';
    
    protected static ?string $title = 'لوحة المعلومات';
    
    protected static ?string $navigationLabel = 'الرئيسية';
    
    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            EducationalStatsWidget::class,
            FinancialStatsWidget::class,
            MarketingStatsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}