<?php

namespace App\Filament\Admin\Resources\DailyCurriculumManagementResource\Pages;

use App\Filament\Admin\Resources\DailyCurriculumManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyCurriculumManagement extends EditRecord
{
    protected static string $resource = DailyCurriculumManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
