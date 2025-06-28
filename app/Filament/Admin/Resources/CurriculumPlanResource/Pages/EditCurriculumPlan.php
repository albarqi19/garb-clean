<?php

namespace App\Filament\Admin\Resources\CurriculumPlanResource\Pages;

use App\Filament\Admin\Resources\CurriculumPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumPlan extends EditRecord
{
    protected static string $resource = CurriculumPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
