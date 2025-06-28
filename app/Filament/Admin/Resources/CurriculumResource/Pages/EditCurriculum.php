<?php

namespace App\Filament\Admin\Resources\CurriculumResource\Pages;

use App\Filament\Admin\Resources\CurriculumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculum extends EditRecord
{
    protected static string $resource = CurriculumResource::class;    protected function getHeaderActions(): array
    {
        return [
            // تم تعطيل زر إضافة الخطط التفصيلية مؤقتًا لإصلاح المشكلة
            /*Actions\Action::make('createBulkPlans')
                ->label('إضافة خطط تفصيلية')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn () => static::$resource::getUrl('create-bulk-plans', ['record' => $this->record])),*/
            Actions\DeleteAction::make(),
        ];
    }
}
