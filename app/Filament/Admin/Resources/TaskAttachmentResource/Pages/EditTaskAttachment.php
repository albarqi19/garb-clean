<?php

namespace App\Filament\Admin\Resources\TaskAttachmentResource\Pages;

use App\Filament\Admin\Resources\TaskAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskAttachment extends EditRecord
{
    protected static string $resource = TaskAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
