<?php

namespace App\Filament\Admin\Resources\MosqueResource\Pages;

use App\Filament\Admin\Resources\MosqueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMosque extends EditRecord
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
