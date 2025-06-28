<?php

namespace App\Filament\Admin\Resources\MarketingTaskWeekResource\Pages;

use App\Filament\Admin\Resources\MarketingTaskWeekResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingTaskWeek extends EditRecord
{
    protected static string $resource = MarketingTaskWeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
