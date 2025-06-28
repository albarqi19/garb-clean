<?php

namespace App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Pages;

use App\Filament\Admin\Resources\TeacherMosqueScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherMosqueSchedules extends ListRecords
{
    protected static string $resource = TeacherMosqueScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة جدول جديد')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherMosqueScheduleResource\Widgets\ScheduleStatsOverview::class,
        ];
    }
}
