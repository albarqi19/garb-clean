<?php

namespace App\Filament\Admin\Resources\DailyCurriculumManagementResource\Pages;

use App\Filament\Admin\Resources\DailyCurriculumManagementResource;
use App\Services\DailyCurriculumTrackingService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class ListDailyCurriculumManagement extends ListRecords
{
    protected static string $resource = DailyCurriculumManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_all_progress')
                ->label('مزامنة جميع التقدمات')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->modalHeading('مزامنة التقدمات')
                ->modalDescription('سيتم مزامنة تقدم جميع الطلاب مع خدمة التتبع اليومي')
                ->modalWidth(MaxWidth::Medium)
                ->requiresConfirmation()
                ->action(function () {
                    $service = app(DailyCurriculumTrackingService::class);
                    $syncCount = 0;
                    
                    try {
                        // الحصول على جميع المناهج النشطة
                        $activeStudentCurricula = $this->getResource()::getModel()::where('is_active', true)->get();
                        
                        foreach ($activeStudentCurricula as $studentCurriculum) {
                            // مزامنة التقدم لكل طالب
                            $dailyCurriculum = $service->getDailyCurriculum($studentCurriculum->student_id);
                            if ($dailyCurriculum) {
                                $syncCount++;
                            }
                        }
                        
                        Notification::make()
                            ->title('تمت المزامنة بنجاح')
                            ->body("تم مزامنة تقدم {$syncCount} طالب")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في المزامنة')
                            ->body('حدث خطأ أثناء المزامنة: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('generate_daily_report')
                ->label('تقرير يومي')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->modalHeading('التقرير اليومي للمناهج')
                ->modalContent(function () {
                    $activeStudents = $this->getResource()::getModel()::with(['student', 'curriculum'])
                        ->where('is_active', true)
                        ->get();
                        
                    $totalStudents = $activeStudents->count();
                    $completedToday = 0; // هذا يمكن حسابه من جلسات اليوم
                    $avgProgress = $activeStudents->avg('current_page') ?? 0;
                    
                    return view('filament.daily-report-modal', [
                        'totalStudents' => $totalStudents,
                        'completedToday' => $completedToday,
                        'avgProgress' => round($avgProgress, 1),
                        'students' => $activeStudents
                    ]);
                })
                ->modalWidth(MaxWidth::Large),
                
            Actions\CreateAction::make()
                ->label('إضافة منهج جديد')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // يمكن إضافة widgets هنا لاحقاً
        ];
    }
}
