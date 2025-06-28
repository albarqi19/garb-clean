<?php

namespace App\Filament\Admin\Resources\RecitationSessionResource\Pages;

use App\Filament\Admin\Resources\RecitationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecitationSession extends EditRecord
{
    protected static string $resource = RecitationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف الجلسة'),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل جلسة التسميع';
    }

    protected function getHeaderTitle(): string
    {
        return 'تعديل جلسة التسميع';
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('حفظ التغييرات');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('إلغاء');
    }
}
