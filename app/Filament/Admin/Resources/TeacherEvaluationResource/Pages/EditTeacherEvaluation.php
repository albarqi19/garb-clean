<?php

namespace App\Filament\Admin\Resources\TeacherEvaluationResource\Pages;

use App\Filament\Admin\Resources\TeacherEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherEvaluation extends EditRecord
{
    protected static string $resource = TeacherEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
