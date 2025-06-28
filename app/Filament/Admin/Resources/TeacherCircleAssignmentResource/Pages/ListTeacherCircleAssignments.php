<?php

namespace App\Filament\Admin\Resources\TeacherCircleAssignmentResource\Pages;

use App\Filament\Admin\Resources\TeacherCircleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherCircleAssignments extends ListRecords
{
    protected static string $resource = TeacherCircleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
