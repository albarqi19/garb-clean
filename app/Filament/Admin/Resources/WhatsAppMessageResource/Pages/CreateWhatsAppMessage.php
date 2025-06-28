<?php

namespace App\Filament\Admin\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Admin\Resources\WhatsAppMessageResource;
use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessage;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppMessage extends CreateRecord
{
    protected static string $resource = WhatsAppMessageResource::class;
    
    protected static ?string $title = 'إرسال رسالة WhatsApp جديدة';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('تفاصيل الرسالة')
                    ->description('املأ البيانات المطلوبة لإرسال رسالة WhatsApp')
                    ->schema([
                        Select::make('user_type')
                            ->label('نوع المستلم')
                            ->options([
                                'teacher' => 'معلم',
                                'student' => 'طالب',
                                'parent' => 'ولي أمر',
                                'admin' => 'إداري',
                                'custom' => 'رقم مخصص'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('user_id', null)),

                        Select::make('user_id')
                            ->label('المستلم')
                            ->options(function ($get) {
                                $userType = $get('user_type');
                                return match($userType) {
                                    'teacher' => \App\Models\Teacher::pluck('name', 'id'),
                                    'student' => \App\Models\Student::pluck('name', 'id'),
                                    default => []
                                };
                            })
                            ->searchable()
                            ->visible(fn ($get) => in_array($get('user_type'), ['teacher', 'student']))
                            ->required(fn ($get) => in_array($get('user_type'), ['teacher', 'student'])),

                        TextInput::make('phone_number')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->visible(fn ($get) => $get('user_type') === 'custom')
                            ->helperText('أدخل رقم الهاتف مع رمز الدولة (مثل: +966501234567)'),

                        Select::make('message_type')
                            ->label('نوع الرسالة')
                            ->options([
                                'notification' => 'إشعار',
                                'welcome' => 'ترحيب',
                                'attendance' => 'حضور',
                                'announcement' => 'إعلان',
                                'reminder' => 'تذكير',
                                'custom' => 'مخصص'
                            ])
                            ->default('custom')
                            ->required(),

                        Textarea::make('message_content')
                            ->label('محتوى الرسالة')
                            ->required()
                            ->rows(5)
                            ->helperText('اكتب محتوى الرسالة هنا'),

                        Textarea::make('notes')
                            ->label('ملاحظات (اختياري)')
                            ->rows(3)
                            ->helperText('ملاحظات إضافية حول الرسالة'),
                    ])
                    ->columns(2)
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // تحديد رقم الهاتف بناءً على نوع المستخدم
        $phoneNumber = null;
        $userId = null;

        if ($data['user_type'] === 'custom') {
            $phoneNumber = $data['phone_number'];
        } elseif ($data['user_type'] === 'teacher') {
            $teacher = \App\Models\Teacher::find($data['user_id']);
            $phoneNumber = $teacher?->phone;
            $userId = $teacher?->id;
        } elseif ($data['user_type'] === 'student') {
            $student = \App\Models\Student::find($data['user_id']);
            $phoneNumber = $student?->phone;
            $userId = $student?->id;
        }

        if (!$phoneNumber) {
            Notification::make()
                ->title('خطأ')
                ->body('لا يمكن العثور على رقم هاتف للمستلم المحدد')
                ->danger()
                ->send();
            
            $this->halt();
        }

        // إنشاء الرسالة
        $message = WhatsAppMessage::createNotification(
            $data['user_type'],
            $userId,
            $phoneNumber,
            $data['message_content'],
            $data['message_type'],
            [
                'notes' => $data['notes'] ?? null,
                'sent_by_admin' => auth()->id(),
                'manual_send' => true
            ]
        );

        // إرسال الرسالة مباشرة بدلاً من استخدام Queue
        try {
            $apiUrl = \App\Models\WhatsAppSetting::get('api_url');
            $apiToken = \App\Models\WhatsAppSetting::get('api_token');
            
            if ($apiUrl && $apiToken) {
                $payload = [
                    'action' => 'send_message',
                    'phone' => $phoneNumber,
                    'message' => $data['message_content']
                ];

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post($apiUrl, $payload);

                if ($response->successful()) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'api_response' => $response->body(),
                    ]);
                    
                    Notification::make()
                        ->title('تم إرسال الرسالة')
                        ->body('تم إرسال الرسالة بنجاح')
                        ->success()
                        ->send();
                } else {
                    $message->update([
                        'status' => 'failed',
                        'error_message' => 'HTTP ' . $response->status() . ': ' . $response->body(),
                    ]);
                    
                    Notification::make()
                        ->title('فشل في الإرسال')
                        ->body('فشل في إرسال الرسالة. كود الخطأ: ' . $response->status())
                        ->warning()
                        ->send();
                }
            } else {
                $message->update([
                    'status' => 'failed',
                    'error_message' => 'API URL أو Token غير مُعرف',
                ]);
                
                Notification::make()
                    ->title('إعدادات ناقصة')
                    ->body('يرجى تكوين إعدادات API في صفحة إعدادات WhatsApp')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title('خطأ في الإرسال')
                ->body('حدث خطأ: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        return $message;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
