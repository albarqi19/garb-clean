<?php

namespace App\Filament\Admin\Resources\AcademicTermResource\Pages;

use App\Filament\Admin\Resources\AcademicTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicTerm extends EditRecord
{
    protected static string $resource = AcademicTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
