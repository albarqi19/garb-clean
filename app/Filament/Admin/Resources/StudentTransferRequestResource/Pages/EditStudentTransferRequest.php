<?php

namespace App\Filament\Admin\Resources\StudentTransferRequestResource\Pages;

use App\Filament\Admin\Resources\StudentTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentTransferRequest extends EditRecord
{
    protected static string $resource = StudentTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
