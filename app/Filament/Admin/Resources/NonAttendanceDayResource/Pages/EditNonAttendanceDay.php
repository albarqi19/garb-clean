<?php

namespace App\Filament\Admin\Resources\NonAttendanceDayResource\Pages;

use App\Filament\Admin\Resources\NonAttendanceDayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNonAttendanceDay extends EditRecord
{
    protected static string $resource = NonAttendanceDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
