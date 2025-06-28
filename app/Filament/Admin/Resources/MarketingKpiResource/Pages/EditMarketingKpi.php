<?php

namespace App\Filament\Admin\Resources\MarketingKpiResource\Pages;

use App\Filament\Admin\Resources\MarketingKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingKpi extends EditRecord
{
    protected static string $resource = MarketingKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
