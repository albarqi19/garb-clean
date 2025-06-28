<?php

namespace App\Filament\Admin\Resources\TeacherTransferRequestResource\Pages;

use App\Filament\Admin\Resources\TeacherTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherTransferRequest extends EditRecord
{
    protected static string $resource = TeacherTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
