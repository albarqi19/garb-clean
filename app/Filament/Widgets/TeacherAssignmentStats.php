<?php

namespace App\Filament\Widgets;

use App\Models\TeacherCircleAssignment;
use App\Models\Teacher;
use App\Models\QuranCircle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherAssignmentStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // إجمالي التكليفات النشطة
        $activeAssignments = TeacherCircleAssignment::active()->count();
        
        // إجمالي التكليفات
        $totalAssignments = TeacherCircleAssignment::count();
        
        // المعلمون الذين لديهم تكليفات متعددة
        $multiAssignmentTeachers = Teacher::whereHas('circleAssignments', function ($query) {
            $query->active();
        }, '>=', 2)->count();
        
        // المعلمون بدون تكليف
        $teachersWithoutAssignment = Teacher::whereDoesntHave('circleAssignments', function ($query) {
            $query->active();
        })->count();
        
        // الحلقات التي لديها معلمون متعددون
        $circlesWithMultipleTeachers = QuranCircle::whereHas('teacherAssignments', function ($query) {
            $query->active();
        }, '>=', 2)->count();
        
        // الحلقات بدون معلمين
        $circlesWithoutTeachers = QuranCircle::whereDoesntHave('teacherAssignments', function ($query) {
            $query->active();
        })->count();

        // التكليفات الجديدة هذا الشهر
        $newAssignmentsThisMonth = TeacherCircleAssignment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // التكليفات المنتهية هذا الشهر
        $endedAssignmentsThisMonth = TeacherCircleAssignment::whereNotNull('end_date')
            ->whereMonth('end_date', now()->month)
            ->whereYear('end_date', now()->year)
            ->count();

        return [
            Stat::make('التكليفات النشطة', $activeAssignments)
                ->description('من إجمالي ' . $totalAssignments . ' تكليف')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 12, 8, 15, 22, 18, $activeAssignments])
                ->color('success'),

            Stat::make('معلمون متعددو الحلقات', $multiAssignmentTeachers)
                ->description('معلمون يعملون في أكثر من حلقة')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('معلمون بدون تكليف', $teachersWithoutAssignment)
                ->description('معلمون متاحون للتكليف')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($teachersWithoutAssignment > 0 ? 'warning' : 'success'),

            Stat::make('حلقات متعددة المعلمين', $circlesWithMultipleTeachers)
                ->description('حلقات لديها أكثر من معلم')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('حلقات بدون معلمين', $circlesWithoutTeachers)
                ->description('حلقات تحتاج لمعلمين')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($circlesWithoutTeachers > 0 ? 'danger' : 'success'),

            Stat::make('تكليفات جديدة', $newAssignmentsThisMonth)
                ->description('هذا الشهر')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),
        ];
    }
}
