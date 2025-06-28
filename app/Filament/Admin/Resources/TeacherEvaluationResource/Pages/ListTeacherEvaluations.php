<?php

namespace App\Filament\Admin\Resources\TeacherEvaluationResource\Pages;

use App\Filament\Admin\Resources\TeacherEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherEvaluations extends ListRecords
{
    protected static string $resource = TeacherEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
