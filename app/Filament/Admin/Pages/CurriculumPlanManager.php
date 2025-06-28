<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class CurriculumPlanManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'إدارة خطط المناهج';
    
    protected static ?string $title = 'إدارة خطط المناهج';
    
    protected static string $view = 'filament.admin.pages.curriculum-plan-manager';
    
    protected static ?string $navigationGroup = 'إدارة المناهج';
    
    protected static ?int $navigationSort = 3;
}
