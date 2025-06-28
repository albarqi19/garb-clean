<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;
    
    // إزالة زر التعديل
    protected function getHeaderActions(): array
    {
        return [
            // لا توجد إجراءات إضافية
        ];
    }
    
    // تغيير عنوان الصفحة
    public function getTitle(): string 
    {
        return 'تفاصيل النشاط';
    }
}
