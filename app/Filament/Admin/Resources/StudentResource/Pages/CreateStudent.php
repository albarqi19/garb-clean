<?php

namespace App\Filament\Admin\Resources\StudentResource\Pages;

use App\Filament\Admin\Resources\StudentResource;
use App\Models\Student;
use App\Helpers\WhatsAppHelper;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // إذا لم يتم إدخال كلمة مرور، قم بتوليد كلمة مرور تلقائياً
        if (empty($data['password'])) {
            $data['password'] = Student::generateRandomPassword();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $student = $this->record;
        $sentMessages = [];
        
        // إرسال رسالة ترحيب للطالب إذا توفر رقم الهاتف
        if ($student && $student->phone) {
            try {
                $sent = WhatsAppHelper::sendStudentWelcome($student);

                if ($sent) {
                    $sentMessages[] = "تم إرسال رسالة ترحيب للطالب {$student->name} على رقم {$student->phone}";
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('فشل في إرسال رسالة الترحيب للطالب')
                    ->body("لم يتم إرسال رسالة الترحيب للطالب: " . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        // إرسال إشعار لولي الأمر إذا توفر رقم هاتفه
        if ($student && $student->guardian_phone) {
            try {
                $sent = WhatsAppHelper::sendParentNotification($student);

                if ($sent) {
                    $sentMessages[] = "تم إرسال إشعار لولي الأمر على رقم {$student->guardian_phone}";
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('فشل في إرسال الإشعار لولي الأمر')
                    ->body("لم يتم إرسال الإشعار لولي الأمر: " . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        // عرض إشعار واحد بجميع الرسائل المرسلة
        if (!empty($sentMessages)) {
            Notification::make()
                ->title('تم إرسال رسائل الترحيب')
                ->body(implode("\n", $sentMessages))
                ->success()
                ->send();
        }
    }
}
