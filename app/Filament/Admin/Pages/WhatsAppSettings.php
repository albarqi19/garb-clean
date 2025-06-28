<?php

namespace App\Filament\Admin\Pages;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class WhatsAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $title = 'إعدادات WhatsApp';
    
    protected static ?string $navigationLabel = 'إعدادات WhatsApp';
    
    protected static ?string $navigationGroup = 'الاتصالات';
    
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.admin.pages.whats-app-settings';

    public ?array $data = [];
    
    protected $listeners = ['refresh' => '$refresh'];

    public function mount(): void
    {
        $this->data = $this->getSettingsData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('إعدادات عامة')
                    ->description('تفعيل وتعطيل إشعارات WhatsApp')
                    ->schema([
                        Toggle::make('notifications_enabled')
                            ->label('تفعيل إشعارات WhatsApp')
                            ->helperText('تفعيل أو تعطيل جميع إشعارات WhatsApp')
                            ->default(false),
                            
                        Toggle::make('teacher_notifications')
                            ->label('إشعارات المعلمين')
                            ->helperText('إرسال إشعارات للمعلمين الجدد')
                            ->default(true),
                            
                        Toggle::make('student_notifications')
                            ->label('إشعارات الطلاب')
                            ->helperText('إرسال إشعارات للطلاب الجدد')
                            ->default(true),
                            
                        Toggle::make('parent_notifications')
                            ->label('إشعارات أولياء الأمور')
                            ->helperText('إرسال إشعارات لأولياء الأمور')
                            ->default(true),
                            
                        Toggle::make('attendance_notifications')
                            ->label('إشعارات الحضور')
                            ->helperText('إرسال إشعارات عند تسجيل الحضور/الغياب')
                            ->default(true),
                            
                        Toggle::make('session_notifications')
                            ->label('إشعارات الجلسات')
                            ->helperText('إرسال إشعارات عند إكمال جلسات التسميع')
                            ->default(true),
                            
                        Toggle::make('notify_teacher_login')
                            ->label('إشعارات تسجيل دخول المعلمين')
                            ->helperText('إرسال إشعار عند تسجيل دخول المعلمين')
                            ->default(true),
                            
                        Toggle::make('notify_supervisor_login')
                            ->label('إشعارات تسجيل دخول المشرفين')
                            ->helperText('إرسال إشعار عند تسجيل دخول المشرفين')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('إعدادات API')
                    ->description('إعدادات الاتصال مع خدمة WhatsApp')
                    ->schema([
                        TextInput::make('api_url')
                            ->label('رابط API')
                            ->required()
                            ->helperText('رابط خدمة WhatsApp API (يمكن استخدام localhost للاختبار)')
                            ->placeholder('مثال: http://localhost:3000/api/webhook/token'),
                            
                        TextInput::make('api_token')
                            ->label('رمز API')
                            ->password()
                            ->required()
                            ->helperText('رمز المصادقة لخدمة WhatsApp API'),
                            
                        TextInput::make('webhook_verify_token')
                            ->label('رمز التحقق من Webhook')
                            ->password()
                            ->helperText('رمز التحقق لاستقبال الرسائل الواردة'),
                    ])
                    ->columns(1),

                Section::make('إعدادات الأداء')
                    ->description('إعدادات معدل الإرسال والمحاولات')
                    ->schema([
                        TextInput::make('rate_limit_per_minute')
                            ->label('حد الإرسال في الدقيقة')
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('عدد الرسائل المسموح إرسالها في الدقيقة الواحدة'),
                            
                        TextInput::make('max_retry_attempts')
                            ->label('عدد المحاولات القصوى')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('عدد المحاولات لإعادة إرسال الرسالة الفاشلة'),
                            
                        TextInput::make('retry_delay_seconds')
                            ->label('فترة التأخير بين المحاولات (ثانية)')
                            ->numeric()
                            ->default(30)
                            ->minValue(5)
                            ->maxValue(3600)
                            ->helperText('الوقت بالثواني للانتظار قبل إعادة المحاولة'),
                    ])
                    ->columns(3),

                Section::make('رسائل اختبار')
                    ->description('اختبار إعدادات WhatsApp')
                    ->schema([
                        TextInput::make('test_phone_number')
                            ->label('رقم هاتف للاختبار')
                            ->tel()
                            ->helperText('أدخل رقم هاتف صالح لاختبار الإرسال'),
                            
                        Textarea::make('test_message')
                            ->label('رسالة الاختبار')
                            ->default('هذه رسالة اختبار من نظام مركز تحفيظ القرآن الكريم')
                            ->rows(3),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->color('primary')
                ->action('save'),
                
            Action::make('test')
                ->label('اختبار الاتصال')
                ->color('warning')
                ->action('testConnection')
                ->visible(function () {
                    $data = $this->data ?? [];
                    return !empty($data['test_phone_number']);
                }),
                
            Action::make('reset')
                ->label('إعادة تعيين')
                ->color('gray')
                ->action('resetToDefaults')
                ->requiresConfirmation(),
        ];
    }

    public function save(): void
    {
        try {
            $state = $this->form->getState();
            $this->data = array_merge($this->data, $state);
            
            foreach ($this->data as $key => $value) {
                if ($key === 'test_phone_number' || $key === 'test_message') {
                    continue; // تجاهل حقول الاختبار
                }
                
                WhatsAppSetting::set($key, $value);
            }

            Notification::make()
                ->title('تم حفظ الإعدادات')
                ->body('تم حفظ إعدادات WhatsApp بنجاح')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في الحفظ')
                ->body('حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testConnection(): void
    {
        try {
            $data = $this->form->getState();
            
            if (empty($data['test_phone_number'])) {
                Notification::make()
                    ->title('رقم هاتف مطلوب')
                    ->body('يرجى إدخال رقم هاتف صالح للاختبار')
                    ->warning()
                    ->send();
                return;
            }

            // إرسال مباشر بدون queue
            $apiUrl = WhatsAppSetting::get('api_url');
            $apiToken = WhatsAppSetting::get('api_token');
            
            if (!$apiUrl || !$apiToken) {
                Notification::make()
                    ->title('إعدادات غير مكتملة')
                    ->body('يرجى تكوين API URL و API Token أولاً')
                    ->warning()
                    ->send();
                return;
            }

            $payload = [
                'action' => 'send_message',
                'phone' => $data['test_phone_number'],
                'message' => $data['test_message'] ?? 'هذه رسالة اختبار من نظام مركز تحفيظ القرآن الكريم'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($apiUrl, $payload);

            if ($response->successful()) {
                // حفظ الرسالة في قاعدة البيانات
                try {
                    WhatsAppMessage::create([
                        'user_type' => 'admin',
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                        'phone_number' => $data['test_phone_number'],
                        'message_type' => 'test',
                        'content' => $payload['message'],
                        'direction' => 'outgoing',
                        'status' => 'sent',
                        'sent_at' => now(),
                        'api_response' => $response->body(),
                    ]);
                } catch (\Exception $e) {
                    // تجاهل أخطاء حفظ قاعدة البيانات لأن الرسالة تم إرسالها بنجاح
                    \Illuminate\Support\Facades\Log::warning('فشل في حفظ رسالة الاختبار في قاعدة البيانات: ' . $e->getMessage());
                }

                Notification::make()
                    ->title('نجح الاختبار')
                    ->body('تم إرسال رسالة الاختبار بنجاح')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('فشل الاختبار')
                    ->body('فشل في إرسال رسالة الاختبار. كود الاستجابة: ' . $response->status())
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في الاختبار')
                ->body('حدث خطأ أثناء اختبار الاتصال: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetToDefaults(): void
    {
        try {
            $this->data = $this->getDefaultSettings();
            $this->form->fill($this->data);
            
            Notification::make()
                ->title('تم إعادة التعيين')
                ->body('تم إعادة تعيين الإعدادات إلى القيم الافتراضية')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في إعادة التعيين')
                ->body('حدث خطأ: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getSettingsData(): array
    {
        return [
            'notifications_enabled' => WhatsAppSetting::get('notifications_enabled', false),
            'teacher_notifications' => WhatsAppSetting::get('teacher_notifications', true),
            'student_notifications' => WhatsAppSetting::get('student_notifications', true),
            'parent_notifications' => WhatsAppSetting::get('parent_notifications', true),
            'attendance_notifications' => WhatsAppSetting::get('attendance_notifications', true),
            'session_notifications' => WhatsAppSetting::get('session_notifications', true),
            'notify_teacher_login' => WhatsAppSetting::get('notify_teacher_login', true),
            'notify_supervisor_login' => WhatsAppSetting::get('notify_supervisor_login', true),
            'api_url' => WhatsAppSetting::get('api_url', ''),
            'api_token' => WhatsAppSetting::get('api_token', ''),
            'webhook_verify_token' => WhatsAppSetting::get('webhook_verify_token', ''),
            'rate_limit_per_minute' => WhatsAppSetting::get('rate_limit_per_minute', '60'),
            'max_retry_attempts' => WhatsAppSetting::get('max_retry_attempts', '3'),
            'retry_delay_seconds' => WhatsAppSetting::get('retry_delay_seconds', '30'),
        ];
    }

    protected function getDefaultSettings(): array
    {
        return [
            'notifications_enabled' => false,
            'teacher_notifications' => true,
            'student_notifications' => true,
            'parent_notifications' => true,
            'attendance_notifications' => true,
            'session_notifications' => true,
            'notify_teacher_login' => true,
            'notify_supervisor_login' => true,
            'api_url' => '',
            'api_token' => '',
            'webhook_verify_token' => '',
            'rate_limit_per_minute' => '60',
            'max_retry_attempts' => '3',
            'retry_delay_seconds' => '30',
        ];
    }

    /**
     * Get count of sent messages
     */
    public function getSentMessagesCount(): int
    {
        return WhatsAppMessage::sent()->count();
    }

    /**
     * Get count of pending messages
     */
    public function getPendingMessagesCount(): int
    {
        return WhatsAppMessage::pending()->count();
    }

    /**
     * Get count of failed messages
     */
    public function getFailedMessagesCount(): int
    {
        return WhatsAppMessage::failed()->count();
    }

    /**
     * Get connection status based on recent successful sends
     */
    public function getConnectionStatus(): bool
    {
        // Check if there were successful sends in the last hour
        $recentSuccess = WhatsAppMessage::sent()
            ->where('sent_at', '>=', Carbon::now()->subHour())
            ->exists();

        // Or check if API settings are configured
        $apiConfigured = !empty(WhatsAppSetting::get('api_url')) && 
                        !empty(WhatsAppSetting::get('api_token'));

        return $recentSuccess || $apiConfigured;
    }

    /**
     * Get recent messages (last 10)
     */
    public function getRecentMessages(): Collection
    {
        return WhatsAppMessage::latest()
            ->limit(10)
            ->get()
            ->map(function ($message) {
                // إضافة معلومات المستخدم بشكل آمن
                $message->user_name = 'غير محدد';
                
                try {
                    if ($message->user_type === 'teacher' && $message->user_id) {
                        $teacher = \App\Models\Teacher::find($message->user_id);
                        $message->user_name = $teacher?->name ?? 'معلم غير موجود';
                    } elseif ($message->user_type === 'student' && $message->user_id) {
                        $student = \App\Models\Student::find($message->user_id);
                        $message->user_name = $student?->name ?? 'طالب غير موجود';
                    } elseif ($message->user_type === 'admin' && $message->user_id) {
                        $admin = \App\Models\User::find($message->user_id);
                        $message->user_name = $admin?->name ?? 'مدير غير موجود';
                    }
                } catch (\Exception $e) {
                    // في حالة حدوث خطأ، نبقي على القيمة الافتراضية
                }
                
                return $message;
            });
    }

    /**
     * Get weekly statistics
     */
    public function getWeeklyStats(): array
    {
        $weekAgo = Carbon::now()->subWeek();
        
        $messages = WhatsAppMessage::where('created_at', '>=', $weekAgo)->get();
        
        $total = $messages->count();
        $sent = $messages->where('status', 'sent')->count();
        $successRate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
        
        $attendance = $messages->where('message_type', 'attendance')->count();
        $notifications = $messages->where('message_type', 'notification')->count();
        $custom = $messages->where('message_type', 'custom')->count();

        return [
            'total' => $total,
            'success_rate' => $successRate,
            'attendance' => $attendance,
            'notifications' => $notifications,
            'custom' => $custom,
        ];
    }

    /**
     * Refresh statistics (called via wire:call)
     */
    public function refreshStats(): void
    {
        // This method allows the frontend to refresh statistics
        // The page will re-render automatically
    }

    /**
     * Get test connection action
     */
    public function getTestConnectionActionProperty(): Action
    {
        return Action::make('testConnection')
            ->label('اختبار الاتصال')
            ->icon('heroicon-o-signal')
            ->color('warning')
            ->size('sm')
            ->action(function () {
                $this->testConnection();
            });
    }

    /**
     * Get reset settings action
     */
    public function getResetSettingsActionProperty(): Action
    {
        return Action::make('resetSettings')
            ->label('إعادة تعيين')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('تأكيد إعادة التعيين')
            ->modalDescription('هل أنت متأكد من إعادة تعيين جميع الإعدادات إلى القيم الافتراضية؟')
            ->modalSubmitActionLabel('إعادة تعيين')
            ->modalCancelActionLabel('إلغاء')
            ->action(function () {
                $this->resetToDefaults();
            });
    }
}
