<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplateResource\Pages;

use App\Filament\Admin\Resources\WhatsAppTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppTemplate extends EditRecord
{
    protected static string $resource = WhatsAppTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
