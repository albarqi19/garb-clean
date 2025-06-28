<?php

namespace App\Filament\Admin\Resources\TeacherResource\Pages;

use App\Filament\Admin\Resources\TeacherResource;
use App\Models\Teacher;
use App\Helpers\WhatsAppHelper;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    // متغير لحفظ كلمة المرور المولدة مؤقتاً
    private ?string $generatedPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // إذا لم يتم إدخال كلمة مرور، قم بتوليد كلمة مرور تلقائياً
        if (empty($data['password'])) {
            $this->generatedPassword = Teacher::generateRandomPassword();
            $data['password'] = $this->generatedPassword;
        } else {
            // حفظ كلمة المرور المدخلة
            $this->generatedPassword = $data['password'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $teacher = $this->record;
        
        // إرسال رسالة ترحيب عبر واتساب إذا توفر رقم الهاتف
        if ($teacher && $teacher->phone) {
            try {
                // إرسال رسالة ترحيب مع كلمة المرور دائماً إذا توفرت كلمة مرور
                if ($this->generatedPassword) {
                    // إرسال كلمة المرور المولدة أو المدخلة
                    $sent = WhatsAppHelper::sendTeacherWelcomeWithPassword($teacher, $this->generatedPassword);
                    
                    $messageType = 'رسالة ترحيب مع كلمة المرور';
                } else {
                    $sent = WhatsAppHelper::sendTeacherWelcome($teacher);
                    $messageType = 'رسالة ترحيب';
                }

                if ($sent) {
                    Notification::make()
                        ->title('تم إرسال رسالة الترحيب')
                        ->body("تم إرسال {$messageType} عبر واتساب للمعلم {$teacher->name} على رقم {$teacher->phone}")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('تحذير')
                        ->body("تم إنشاء المعلم بنجاح لكن لم يتم إرسال رسالة الترحيب عبر واتساب")
                        ->warning()
                        ->send();
                }
            } catch (\Exception $e) {
                // في حالة فشل الإرسال، نعرض إشعار بالخطأ
                Notification::make()
                    ->title('فشل في إرسال رسالة الترحيب')
                    ->body("تم إنشاء المعلم بنجاح لكن فشل إرسال رسالة الترحيب: " . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}
