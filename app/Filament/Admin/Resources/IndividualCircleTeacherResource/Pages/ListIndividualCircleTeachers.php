<?php

namespace App\Filament\Admin\Resources\IndividualCircleTeacherResource\Pages;

use App\Filament\Admin\Resources\IndividualCircleTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIndividualCircleTeachers extends ListRecords
{
    protected static string $resource = IndividualCircleTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
