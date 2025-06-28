<?php

namespace App\Filament\Admin\Resources\StudentCurriculumResource\Pages;

use App\Filament\Admin\Resources\StudentCurriculumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentCurricula extends ListRecords
{
    protected static string $resource = StudentCurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
