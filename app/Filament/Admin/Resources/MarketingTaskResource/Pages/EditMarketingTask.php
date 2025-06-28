<?php

namespace App\Filament\Admin\Resources\MarketingTaskResource\Pages;

use App\Filament\Admin\Resources\MarketingTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingTask extends EditRecord
{
    protected static string $resource = MarketingTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
