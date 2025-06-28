<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class WhatsAppManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'إدارة الواتساب';
    protected static ?string $title = 'إدارة رسائل الواتساب';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.admin.pages.whatsapp-manager';

    public function getTitle(): string
    {
        return 'إدارة رسائل الواتساب العالقة';
    }

    public function getHeading(): string
    {
        return 'إدارة رسائل الواتساب العالقة';
    }

    public function getSubheading(): ?string
    {
        return 'أدوات لمعالجة وإصلاح مشاكل إرسال رسائل الواتساب';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('تحديث البيانات')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('refresh-stats')),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('show_status')
                ->label('عرض حالة النظام')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(function () {
                    $stats = $this->getSystemStats();
                    
                    Notification::make()
                        ->title('حالة نظام الواتساب')
                        ->body("الرسائل المعلقة: {$stats['pending_messages']} | الرسائل الفاشلة: {$stats['failed_messages']} | المهام في القائمة: {$stats['queue_jobs']} | المهام الفاشلة: {$stats['failed_jobs']}")
                        ->info()
                        ->duration(10000)
                        ->send();
                }),

            Action::make('retry_failed')
                ->label('إعادة محاولة الرسائل الفاشلة')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة محاولة الرسائل الفاشلة')
                ->modalDescription('سيتم إعادة إضافة جميع الرسائل الفاشلة إلى قائمة الانتظار للإرسال مرة أخرى.')
                ->action(function () {
                    try {
                        $failedMessages = WhatsAppMessage::where('status', 'failed')->get();
                        $count = $failedMessages->count();
                        
                        if ($count === 0) {
                            Notification::make()
                                ->title('لا توجد رسائل فاشلة')
                                ->body('لا توجد رسائل فاشلة لإعادة المحاولة.')
                                ->info()
                                ->send();
                            return;
                        }
                        
                        foreach ($failedMessages as $message) {
                            $message->update([
                                'status' => 'pending',
                                'error_message' => null,
                                'failed_at' => null,
                            ]);
                            \App\Jobs\SendWhatsAppMessage::dispatch($message->id);
                        }
                        
                        Notification::make()
                            ->title('تم إعادة المحاولة')
                            ->body("تم إعادة إضافة {$count} رسالة فاشلة إلى قائمة الانتظار.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في إعادة المحاولة')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('process_pending')
                ->label('معالجة الرسائل المعلقة')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(function () {
                    try {
                        $pendingMessages = WhatsAppMessage::where('status', 'pending')->get();
                        $count = $pendingMessages->count();
                        
                        if ($count === 0) {
                            Notification::make()
                                ->title('لا توجد رسائل معلقة')
                                ->body('لا توجد رسائل معلقة للمعالجة.')
                                ->info()
                                ->send();
                            return;
                        }
                        
                        foreach ($pendingMessages as $message) {
                            \App\Jobs\SendWhatsAppMessage::dispatch($message->id);
                        }
                        
                        // تشغيل معالج قائمة الانتظار
                        Artisan::call('queue:work', ['--once' => true, '--timeout' => 60]);
                        
                        Notification::make()
                            ->title('تم بدء المعالجة')
                            ->body("تم إضافة {$count} رسالة معلقة إلى قائمة الانتظار وبدء المعالجة.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في المعالجة')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clear_queue')
                ->label('مسح قائمة الانتظار')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('مسح قائمة الانتظار')
                ->modalDescription('تحذير! سيتم مسح جميع المهام المعلقة والفاشلة من قائمة الانتظار. هذا الإجراء لا يمكن التراجع عنه.')
                ->action(function () {
                    try {
                        $pendingJobs = DB::table('jobs')->count();
                        $failedJobs = DB::table('failed_jobs')->count();
                        
                        // مسح الوظائف المعلقة والفاشلة
                        DB::table('jobs')->truncate();
                        DB::table('failed_jobs')->truncate();
                        
                        // إعادة تشغيل queue
                        Artisan::call('queue:restart');
                        
                        Notification::make()
                            ->title('تم مسح قائمة الانتظار')
                            ->body("تم مسح {$pendingJobs} مهمة معلقة و {$failedJobs} مهمة فاشلة وإعادة تشغيل النظام.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في مسح القائمة')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('restart_queue')
                ->label('إعادة تشغيل النظام')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    try {
                        Artisan::call('queue:restart');
                        
                        Notification::make()
                            ->title('تم إعادة التشغيل')
                            ->body('تم إعادة تشغيل نظام قائمة الانتظار بنجاح.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في إعادة التشغيل')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('run_command')
                ->label('تشغيل أمر إدارة الواتساب')
                ->icon('heroicon-o-command-line')
                ->color('purple')
                ->action(function () {
                    try {
                        // تشغيل أمر إدارة الواتساب
                        Artisan::call('whatsapp:manage', ['action' => 'send']);
                        $output = Artisan::output();
                        
                        Notification::make()
                            ->title('تم تشغيل الأمر')
                            ->body('تم تشغيل أمر إدارة الواتساب بنجاح.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في تشغيل الأمر')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getSystemStats(): array
    {
        return [
            'pending_messages' => WhatsAppMessage::where('status', 'pending')->count(),
            'sent_messages' => WhatsAppMessage::where('status', 'sent')->count(),
            'failed_messages' => WhatsAppMessage::where('status', 'failed')->count(),
            'total_messages' => WhatsAppMessage::count(),
            'queue_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'recent_messages' => WhatsAppMessage::where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    public function getViewData(): array
    {
        return [
            'stats' => $this->getSystemStats(),
        ];
    }
}
