<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DailyCurriculumManagementResource\Pages;
use App\Models\DailyCurriculumManagement;
use App\Models\Student;
use App\Models\Curriculum;
use App\Services\DailyCurriculumTrackingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DailyCurriculumManagementResource extends Resource
{
    protected static ?string $model = DailyCurriculumManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'المنهج اليومي للطلاب';
    
    protected static ?string $modelLabel = 'منهج يومي';
    
    protected static ?string $pluralModelLabel = 'المناهج اليومية';
    
    protected static ?string $navigationGroup = 'إدارة الطلاب والمناهج';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('بيانات الطالب والمنهج')
                    ->description('تحديد الطالب والمنهج المراد إدارته')
                    ->schema([
                        Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Select::make('curriculum_id')
                            ->label('المنهج')
                            ->relationship('curriculum', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
                    
                Section::make('إعدادات المنهج اليومي')
                    ->description('تحديد المحتوى اليومي للحفظ والمراجعة')
                    ->schema([
                        TextInput::make('daily_memorization_pages')
                            ->label('عدد صفحات الحفظ اليومي')
                            ->numeric()
                            ->default(1)
                            ->minValue(0.5)
                            ->maxValue(5)
                            ->step(0.5)
                            ->suffix('صفحة')
                            ->helperText('عدد الصفحات المطلوب حفظها يومياً'),
                            
                        TextInput::make('daily_minor_review_pages')
                            ->label('عدد صفحات المراجعة الصغرى')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(10)
                            ->suffix('صفحة')
                            ->helperText('المراجعة القريبة (آخر 7 أيام)'),
                            
                        TextInput::make('daily_major_review_pages')
                            ->label('عدد صفحات المراجعة الكبرى')
                            ->numeric()
                            ->default(5)
                            ->minValue(2)
                            ->maxValue(20)
                            ->suffix('صفحة')
                            ->helperText('المراجعة العامة (المحفوظ السابق)'),
                    ])
                    ->columns(3),
                    
                Section::make('حالة التقدم')
                    ->description('متابعة تقدم الطالب في المنهج')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required(),
                            
                        TextInput::make('current_page')
                            ->label('الصفحة الحالية')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(604)
                            ->helperText('آخر صفحة تم حفظها'),
                            
                        TextInput::make('current_surah')
                            ->label('السورة الحالية')
                            ->maxLength(100)
                            ->helperText('اسم السورة الحالية'),
                            
                        TextInput::make('current_ayah')
                            ->label('الآية الحالية')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('رقم الآية الحالية'),
                    ])
                    ->columns(2),
                    
                Section::make('الحالة والملاحظات')
                    ->schema([
                        Select::make('status')
                            ->label('حالة المنهج')
                            ->options([
                                'قيد التنفيذ' => 'قيد التنفيذ',
                                'مكتمل' => 'مكتمل',
                                'متوقف مؤقتاً' => 'متوقف مؤقتاً',
                                'ملغي' => 'ملغي',
                            ])
                            ->default('قيد التنفيذ')
                            ->required(),
                            
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('هل المنهج نشط حالياً؟'),
                            
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('ملاحظات إضافية حول تقدم الطالب'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                TextColumn::make('student.quranCircle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->placeholder('بدون حلقة'),
                    
                                TextColumn::make('curriculum.name')
                    ->label('المنهج')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('curriculum.type')
                    ->label('نوع المنهج')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منهج تلقين' => 'info',
                        'منهج طالب' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('current_page')
                    ->label('الصفحة الحالية')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('current_surah')
                    ->label('السورة الحالية')
                    ->searchable()
                    ->limit(20),
                    
                TextColumn::make('progress_percentage')
                    ->label('نسبة التقدم')
                    ->getStateUsing(function ($record) {
                        $totalPages = 604;
                        $currentPage = $record->current_page ?? 1;
                        return round(($currentPage / $totalPages) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $percentage = floatval(str_replace('%', '', $state));
                        if ($percentage >= 80) return 'success';
                        if ($percentage >= 50) return 'warning';
                        return 'danger';
                    }),
                    
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'قيد التنفيذ' => 'success',
                            'مكتمل' => 'primary',
                            'متوقف مؤقتاً' => 'warning',
                            'ملغي' => 'danger',
                            default => 'gray',
                        };
                    }),
                    
                TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curriculum_id')
                    ->label('المنهج')
                    ->relationship('curriculum', 'name'),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                        'متوقف مؤقتاً' => 'متوقف مؤقتاً',
                        'ملغي' => 'ملغي',
                    ]),
            ])
            ->actions([
                Action::make('view_daily_plan')
                    ->label('المنهج اليومي')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->modalHeading('المنهج اليومي')
                    ->modalDescription('عرض تفاصيل المنهج اليومي للطالب')
                    ->modalContent(function ($record) {
                        $service = app(DailyCurriculumTrackingService::class);
                        $dailyCurriculum = $service->getDailyCurriculum($record->student_id);
                        
                        return view('filament.daily-curriculum-modal', [
                            'student' => $record->student,
                            'curriculum' => $record->curriculum,
                            'dailyCurriculum' => $dailyCurriculum,
                            'record' => $record
                        ]);
                    })
                    ->modalWidth('lg'),
                    
                Action::make('complete_day')
                    ->label('إكمال اليوم')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إكمال اليوم')
                    ->modalDescription('هل تريد تسجيل إكمال المنهج اليومي لهذا الطالب؟')
                    ->action(function ($record) {
                        $service = app(DailyCurriculumTrackingService::class);
                        
                        try {
                            // محاكاة جلسة تسميع مكتملة
                            $sessionData = [
                                'student_id' => $record->student_id,
                                'curriculum_id' => $record->curriculum_id,
                                'session_date' => now()->format('Y-m-d'),
                                'status' => 'مكتمل',
                                'daily_memorization_completed' => true,
                                'daily_minor_review_completed' => true,
                                'daily_major_review_completed' => true,
                            ];
                            
                            $service->updateProgressAfterSession($sessionData);
                            
                            Notification::make()
                                ->title('تم إكمال اليوم بنجاح')
                                ->body("تم تحديث تقدم الطالب: {$record->student->name}")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('خطأ في إكمال اليوم')
                                ->body('حدث خطأ أثناء تحديث التقدم: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('reset_progress')
                    ->label('إعادة تعيين التقدم')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إعادة التعيين')
                    ->modalDescription('هل تريد إعادة تعيين تقدم الطالب إلى بداية المنهج؟')
                    ->action(function ($record) {
                        $record->update([
                            'current_page' => 1,
                            'current_surah' => 'الفاتحة',
                            'current_ayah' => 1,
                        ]);
                        
                        Notification::make()
                            ->title('تم إعادة تعيين التقدم')
                            ->body("تم إعادة تقدم الطالب {$record->student->name} إلى البداية")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyCurriculumManagement::route('/'),
            'create' => Pages\CreateDailyCurriculumManagement::route('/create'),
            'edit' => Pages\EditDailyCurriculumManagement::route('/{record}/edit'),
        ];
    }
}
