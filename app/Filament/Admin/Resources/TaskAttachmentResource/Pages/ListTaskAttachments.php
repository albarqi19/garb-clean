<?php

namespace App\Filament\Admin\Resources\TaskAttachmentResource\Pages;

use App\Filament\Admin\Resources\TaskAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskAttachments extends ListRecords
{
    protected static string $resource = TaskAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
