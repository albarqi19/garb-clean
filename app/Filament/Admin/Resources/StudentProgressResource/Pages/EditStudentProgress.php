<?php

namespace App\Filament\Admin\Resources\StudentProgressResource\Pages;

use App\Filament\Admin\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentProgress extends EditRecord
{
    protected static string $resource = StudentProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
