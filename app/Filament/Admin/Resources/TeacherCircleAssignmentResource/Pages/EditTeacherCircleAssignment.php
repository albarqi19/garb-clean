<?php

namespace App\Filament\Admin\Resources\TeacherCircleAssignmentResource\Pages;

use App\Filament\Admin\Resources\TeacherCircleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherCircleAssignment extends EditRecord
{
    protected static string $resource = TeacherCircleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
