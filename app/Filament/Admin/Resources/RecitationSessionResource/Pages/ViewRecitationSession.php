<?php

namespace App\Filament\Admin\Resources\RecitationSessionResource\Pages;

use App\Filament\Admin\Resources\RecitationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;

class ViewRecitationSession extends ViewRecord
{
    protected static string $resource = RecitationSessionResource::class;

    protected static ?string $title = 'عرض جلسة التسميع';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextEntry::make('student.name')
                            ->label('الطالب')
                            ->icon('heroicon-o-user'),
                        
                        TextEntry::make('circle.name')
                            ->label('الحلقة')
                            ->icon('heroicon-o-user-group'),
                        
                        TextEntry::make('teacher.name')
                            ->label('المعلم')
                            ->icon('heroicon-o-academic-cap'),
                        
                        TextEntry::make('session_date')
                            ->label('تاريخ ووقت الجلسة')
                            ->dateTime('Y-m-d H:i')
                            ->icon('heroicon-o-calendar'),
                    ])->columns(2),

                Section::make('النطاق القرآني')
                    ->schema([
                        TextEntry::make('surah_start')
                            ->label('السورة الأولى')
                            ->formatStateUsing(fn ($state) => "سورة رقم {$state}"),
                        
                        TextEntry::make('ayah_start')
                            ->label('الآية الأولى'),
                        
                        TextEntry::make('surah_end')
                            ->label('السورة الأخيرة')
                            ->formatStateUsing(fn ($state) => "سورة رقم {$state}"),
                        
                        TextEntry::make('ayah_end')
                            ->label('الآية الأخيرة'),
                        
                        TextEntry::make('range_summary')
                            ->label('ملخص النطاق')
                            ->formatStateUsing(function ($record) {
                                $start = "سورة {$record->surah_start} آية {$record->ayah_start}";
                                $end = "سورة {$record->surah_end} آية {$record->ayah_end}";
                                return "{$start} - {$end}";
                            })
                            ->columnSpanFull(),
                    ])->columns(4),

                Section::make('التقييم والنتائج')
                    ->schema([
                        BadgeEntry::make('grade')
                            ->label('الدرجة')
                            ->color(fn (string $state): string => match (true) {
                                $state >= 90 => 'success',
                                $state >= 80 => 'info',
                                $state >= 70 => 'warning',
                                $state >= 60 => 'gray',
                                default => 'danger',
                            }),
                        
                        BadgeEntry::make('rating')
                            ->label('التقدير')
                            ->color(fn (string $state): string => match ($state) {
                                'ممتاز' => 'success',
                                'جيد جداً' => 'info',
                                'جيد' => 'warning',
                                'مقبول' => 'gray',
                                'ضعيف' => 'danger',
                                default => 'gray',
                            }),
                        
                        BadgeEntry::make('total_errors')
                            ->label('إجمالي الأخطاء')
                            ->color(fn (int $state): string => match (true) {
                                $state === 0 => 'success',
                                $state <= 3 => 'warning',
                                default => 'danger',
                            }),
                        
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime()
                            ->since(),
                    ])->columns(4),

                Section::make('الملاحظات والتوصيات')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                        
                        TextEntry::make('recommendations')
                            ->label('التوصيات')
                            ->placeholder('لا توجد توصيات')
                            ->columnSpanFull(),
                    ])->collapsible(),

                Section::make('الأخطاء المسجلة')
                    ->schema([
                        TextEntry::make('errors_count')
                            ->label('عدد الأخطاء المسجلة')
                            ->formatStateUsing(fn ($record) => $record->errors()->count() . ' خطأ'),
                        
                        TextEntry::make('errors_summary')
                            ->label('ملخص الأخطاء')
                            ->formatStateUsing(function ($record) {
                                $errors = $record->errors;
                                if ($errors->isEmpty()) {
                                    return 'لا توجد أخطاء مسجلة';
                                }
                                
                                $summary = [];
                                $errorTypes = $errors->groupBy('error_type');
                                
                                foreach ($errorTypes as $type => $typeErrors) {
                                    $count = $typeErrors->count();
                                    $summary[] = "{$type}: {$count}";
                                }
                                
                                return implode(' | ', $summary);
                            })
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(),

                Section::make('إحصائيات إضافية')
                    ->schema([
                        TextEntry::make('session_duration')
                            ->label('مدة الجلسة التقديرية')
                            ->formatStateUsing(function ($record) {
                                // Calculate estimated duration based on range
                                $totalAyahs = $record->ayah_end - $record->ayah_start + 1;
                                $estimatedMinutes = $totalAyahs * 0.5; // Estimate 30 seconds per ayah
                                return round($estimatedMinutes) . ' دقيقة (تقديرية)';
                            }),
                        
                        TextEntry::make('performance_level')
                            ->label('مستوى الأداء')
                            ->formatStateUsing(function ($record) {
                                $grade = $record->grade;
                                $errors = $record->total_errors;
                                
                                $performance = '';
                                if ($grade >= 90 && $errors <= 1) {
                                    $performance = 'متميز جداً';
                                } elseif ($grade >= 80 && $errors <= 3) {
                                    $performance = 'متميز';
                                } elseif ($grade >= 70 && $errors <= 5) {
                                    $performance = 'جيد';
                                } elseif ($grade >= 60) {
                                    $performance = 'مقبول';
                                } else {
                                    $performance = 'يحتاج تحسين';
                                }
                                
                                return $performance;
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'متميز جداً' => 'success',
                                'متميز' => 'info',
                                'جيد' => 'warning',
                                'مقبول' => 'gray',
                                'يحتاج تحسين' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2)->collapsible(),
            ]);
    }
}
