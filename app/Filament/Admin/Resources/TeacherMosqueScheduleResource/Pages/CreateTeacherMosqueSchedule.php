<?php

namespace App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Pages;

use App\Filament\Admin\Resources\TeacherMosqueScheduleResource;
use App\Models\TeacherMosqueSchedule;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTeacherMosqueSchedule extends CreateRecord
{
    protected static string $resource = TeacherMosqueScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجدول بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // التحقق من تعارض المواعيد
        $conflictingSchedule = TeacherMosqueSchedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('is_active', true)
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_time', '<=', $data['start_time'])
                          ->where('end_time', '>=', $data['end_time']);
                    });
            })
            ->first();

        if ($conflictingSchedule) {
            Notification::make()
                ->title('تعارض في الجدول!')
                ->body('يوجد جدول آخر للمعلم في نفس التوقيت في مسجد: ' . $conflictingSchedule->mosque->name)
                ->danger()
                ->send();
            
            $this->halt();
        }

        return $data;
    }
}
