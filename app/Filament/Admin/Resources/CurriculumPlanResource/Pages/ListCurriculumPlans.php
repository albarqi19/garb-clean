<?php

namespace App\Filament\Admin\Resources\CurriculumPlanResource\Pages;

use App\Filament\Admin\Resources\CurriculumPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumPlans extends ListRecords
{
    protected static string $resource = CurriculumPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
