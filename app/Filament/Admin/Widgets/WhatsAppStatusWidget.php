<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;

class WhatsAppStatusWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.whatsapp-status-widget';
    
    protected static ?string $heading = 'حالة رسائل الواتساب';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $stats = [
            'pending_messages' => WhatsAppMessage::where('status', 'pending')->count(),
            'failed_messages' => WhatsAppMessage::where('status', 'failed')->count(),
            'queue_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        $hasIssues = $stats['pending_messages'] > 10 || 
                    $stats['failed_messages'] > 0 || 
                    $stats['queue_jobs'] > 20 || 
                    $stats['failed_jobs'] > 0;

        return [
            'stats' => $stats,
            'hasIssues' => $hasIssues,
        ];
    }

    public static function canView(): bool
    {
        // عرض الودجيت فقط إذا كان هناك مشاكل في الرسائل
        $pendingMessages = WhatsAppMessage::where('status', 'pending')->count();
        $failedMessages = WhatsAppMessage::where('status', 'failed')->count();
        $queueJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        return $pendingMessages > 10 || $failedMessages > 0 || $queueJobs > 20 || $failedJobs > 0;
    }
}
