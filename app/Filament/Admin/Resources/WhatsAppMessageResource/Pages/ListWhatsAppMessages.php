<?php

namespace App\Filament\Admin\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Admin\Resources\WhatsAppMessageResource;
use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessage;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppMessages extends ListRecords
{
    protected static string $resource = WhatsAppMessageResource::class;
    
    protected static ?string $title = 'رسائل WhatsApp';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إرسال رسالة جديدة')
                ->icon('heroicon-o-plus'),

            Action::make('retry_failed')
                ->label('إعادة إرسال الرسائل الفاشلة')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة إرسال الرسائل الفاشلة')
                ->modalDescription('هل تريد إعادة إرسال جميع الرسائل الفاشلة؟')
                ->action(function () {
                    $failedMessages = WhatsAppMessage::where('status', 'failed')->get();
                    
                    foreach ($failedMessages as $message) {
                        $message->update([
                            'status' => 'pending',
                            'error_message' => null,
                            'failed_at' => null,
                        ]);
                        
                        SendWhatsAppMessage::dispatch($message->id);
                    }

                    Notification::make()
                        ->title('تم إعادة الإرسال')
                        ->body("تم إعادة إرسال {$failedMessages->count()} رسالة فاشلة")
                        ->success()
                        ->send();
                })
                ->visible(fn () => WhatsAppMessage::where('status', 'failed')->exists()),

            Action::make('test_connection')
                ->label('اختبار الاتصال')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('test_phone')
                        ->label('رقم الهاتف للاختبار')
                        ->tel()
                        ->required()
                        ->helperText('أدخل رقم هاتف صالح لاختبار الاتصال'),
                ])
                ->action(function (array $data) {
                    $whatsAppService = app(\App\Services\WhatsAppService::class);
                    
                    $success = $whatsAppService->testConnection($data['test_phone']);
                    
                    if ($success) {
                        Notification::make()
                            ->title('نجح الاختبار')
                            ->body('تم إرسال رسالة اختبار بنجاح')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('فشل الاختبار')
                            ->body('فشل في إرسال رسالة الاختبار')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clear_old_messages')
                ->label('حذف الرسائل القديمة')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('حذف الرسائل القديمة')
                ->modalDescription('هل تريد حذف الرسائل الأقدم من شهر؟ (سيتم الاحتفاظ بالرسائل المرسلة والفاشلة)')
                ->action(function () {
                    $deleted = WhatsAppMessage::where('created_at', '<', now()->subMonth())
                        ->whereIn('status', ['sent'])
                        ->delete();

                    Notification::make()
                        ->title('تم الحذف')
                        ->body("تم حذف {$deleted} رسالة قديمة")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'إدارة رسائل WhatsApp';
    }
}
