<?php

namespace App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Widgets;

use App\Models\TeacherMosqueSchedule;
use App\Models\Teacher;
use App\Models\Mosque;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ScheduleStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSchedules = TeacherMosqueSchedule::count();
        $activeSchedules = TeacherMosqueSchedule::where('is_active', true)->count();
        $teachersWithMultipleMosques = Teacher::whereHas('mosqueSchedules', function ($query) {
            $query->where('is_active', true);
        })->withCount(['mosqueSchedules' => function ($query) {
            $query->where('is_active', true)->distinct('mosque_id');
        }])->having('mosque_schedules_count', '>', 1)->count();
        
        $mosquesWithTeachers = Mosque::whereHas('teacherSchedules', function ($query) {
            $query->where('is_active', true);
        })->count();

        return [
            Stat::make('إجمالي الجداول', $totalSchedules)
                ->description('عدد جداول المعلمين في جميع المساجد')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('الجداول النشطة', $activeSchedules)
                ->description('الجداول المفعلة حالياً')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('معلمون متعددو المساجد', $teachersWithMultipleMosques)
                ->description('المعلمون الذين يعملون في أكثر من مسجد')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('warning'),
                
            Stat::make('مساجد بها معلمون', $mosquesWithTeachers)
                ->description('المساجد التي تحتوي على معلمين مجدولين')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),
        ];
    }
}
