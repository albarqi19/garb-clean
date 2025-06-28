<?php

namespace App\Filament\Admin\Resources\TeacherIncentiveResource\Pages;

use App\Filament\Admin\Resources\TeacherIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherIncentive extends EditRecord
{
    protected static string $resource = TeacherIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
