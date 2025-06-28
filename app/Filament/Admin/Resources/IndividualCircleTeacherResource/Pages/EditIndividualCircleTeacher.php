<?php

namespace App\Filament\Admin\Resources\IndividualCircleTeacherResource\Pages;

use App\Filament\Admin\Resources\IndividualCircleTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIndividualCircleTeacher extends EditRecord
{
    protected static string $resource = IndividualCircleTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
