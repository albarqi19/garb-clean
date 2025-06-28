<?php

namespace App\Filament\Admin\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Admin\Resources\WhatsAppMessageResource;
use App\Jobs\SendWhatsAppMessage;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppMessage extends ViewRecord
{
    protected static string $resource = WhatsAppMessageResource::class;
    
    protected static ?string $title = 'عرض تفاصيل رسالة WhatsApp';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label('إعادة الإرسال')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => in_array($this->record->status, ['failed', 'pending']))
                ->requiresConfirmation()
                ->modalHeading('إعادة إرسال الرسالة')
                ->modalDescription('هل تريد إعادة إرسال هذه الرسالة؟')
                ->action(function () {
                    // تحديث حالة الرسالة
                    $this->record->update([
                        'status' => 'pending',
                        'error_message' => null,
                        'failed_at' => null,
                    ]);

                    // إرسال الرسالة مجدداً
                    SendWhatsAppMessage::dispatch($this->record->id);

                    Notification::make()
                        ->title('تم إعادة الإرسال')
                        ->body('تم إضافة الرسالة لطابور الإرسال مجدداً')
                        ->success()
                        ->send();
                }),

            Action::make('mark_as_sent')
                ->label('تعيين كمرسلة')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'sent')
                ->requiresConfirmation()
                ->modalHeading('تعيين الرسالة كمرسلة')
                ->modalDescription('هل تريد تعيين هذه الرسالة كمرسلة يدوياً؟')
                ->action(function () {
                    $this->record->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'error_message' => null,
                    ]);

                    Notification::make()
                        ->title('تم التحديث')
                        ->body('تم تعيين الرسالة كمرسلة')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->label('حذف الرسالة')
                ->visible(fn () => in_array($this->record->status, ['failed', 'pending'])),
        ];
    }

    public function getTitle(): string
    {
        return "رسالة WhatsApp #{$this->record->id}";
    }
}
