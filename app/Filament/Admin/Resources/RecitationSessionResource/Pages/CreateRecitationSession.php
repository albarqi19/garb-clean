<?php

namespace App\Filament\Admin\Resources\RecitationSessionResource\Pages;

use App\Filament\Admin\Resources\RecitationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecitationSession extends CreateRecord
{
    protected static string $resource = RecitationSessionResource::class;

    public function getTitle(): string
    {
        return 'إضافة جلسة تسميع';
    }

    protected function getHeaderTitle(): string
    {
        return 'إضافة جلسة تسميع جديدة';
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('حفظ الجلسة');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('إلغاء');
    }
}
