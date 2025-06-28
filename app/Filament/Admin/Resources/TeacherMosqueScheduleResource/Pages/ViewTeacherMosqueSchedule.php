<?php

namespace App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Pages;

use App\Filament\Admin\Resources\TeacherMosqueScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherMosqueSchedule extends ViewRecord
{
    protected static string $resource = TeacherMosqueScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
