<?php

namespace App\Filament\Admin\Resources\MarketingActivityResource\Pages;

use App\Filament\Admin\Resources\MarketingActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingActivity extends EditRecord
{
    protected static string $resource = MarketingActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
