<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MarketingTaskResource\Pages;
use App\Filament\Admin\Resources\MarketingTaskResource\RelationManagers;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\MarketingTaskWeek;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MarketingTaskResource extends Resource
{
    protected static ?string $model = MarketingTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    // تعريب المورد
    protected static ?string $modelLabel = 'مهمة تسويقية';
    protected static ?string $pluralModelLabel = 'المهام التسويقية';
    protected static ?string $navigationGroup = 'إدارة التسويق';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المهمة الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المهمة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المهمة')
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('marketing_task_week_id')
                            ->label('أسبوع المهمة')
                            ->options(function() {
                                $currentDate = Carbon::now();
                                $currentWeek = $currentDate->weekOfYear;
                                $currentYear = $currentDate->year;
                                
                                // إحضار الأسابيع الحالية والسابقة والقادمة
                                return MarketingTaskWeek::where('year', '>=', $currentYear - 1)
                                    ->where('year', '<=', $currentYear + 1)
                                    ->orderBy('year', 'desc')
                                    ->orderBy('week_number', 'desc')
                                    ->get()
                                    ->mapWithKeys(function($week) {
                                        return [$week->id => "الأسبوع {$week->week_number} - {$week->year}"];
                                    });
                            })
                            ->searchable()
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('تفاصيل الجدولة')
                    ->schema([
                        Forms\Components\Select::make('schedule_type')
                            ->label('نوع الجدولة')
                            ->options([
                                'يومي' => 'يومي',
                                'أسبوعي' => 'أسبوعي',
                                'شهري' => 'شهري',
                                'مرة واحدة' => 'مرة واحدة',
                            ])
                            ->required()
                            ->reactive(),
                            
                        Forms\Components\Select::make('day_of_week')
                            ->label('يوم الأسبوع')
                            ->options([
                                'الأحد' => 'الأحد',
                                'الاثنين' => 'الاثنين',
                                'الثلاثاء' => 'الثلاثاء',
                                'الأربعاء' => 'الأربعاء',
                                'الخميس' => 'الخميس',
                                'الجمعة' => 'الجمعة',
                                'السبت' => 'السبت',
                            ])
                            ->visible(fn (callable $get) => in_array($get('schedule_type'), ['أسبوعي'])),
                            
                        Forms\Components\Select::make('time_of_day')
                            ->label('وقت اليوم')
                            ->options([
                                'صباحاً' => 'صباحاً',
                                'ظهراً' => 'ظهراً',
                                'مساءً' => 'مساءً',
                            ]),
                        
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'منخفضة' => 'منخفضة',
                                'عادية' => 'عادية',
                                'عالية' => 'عالية',
                                'حرجة' => 'حرجة',
                            ])
                            ->default('عادية')
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('القنوات والمسؤولية')
                    ->schema([
                        Forms\Components\Select::make('channel')
                            ->label('القناة التسويقية')
                            ->options([
                                'منصات التواصل' => 'منصات التواصل',
                                'إيميل' => 'إيميل',
                                'واتساب' => 'واتساب',
                                'إعلانات مدفوعة' => 'إعلانات مدفوعة',
                                'موقع إلكتروني' => 'موقع إلكتروني',
                                'تقارير' => 'تقارير',
                                'تخطيط' => 'تخطيط',
                                'اجتماع' => 'اجتماع',
                                'أخرى' => 'أخرى',
                            ])
                            ->searchable(),
                        
                        Forms\Components\Select::make('assigned_to')
                            ->label('المسؤول عن التنفيذ')
                            ->relationship('assignedUser', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'marketing' => 'تسويق عام',
                                'social_media' => 'منصات التواصل',
                                'analytics' => 'تحليلات وتقارير',
                                'campaigns' => 'حملات تسويقية',
                                'content' => 'إنتاج محتوى',
                                'planning' => 'تخطيط',
                                'other' => 'أخرى',
                            ])
                            ->default('marketing')
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->placeholder('أضف أي ملاحظات إضافية هنا')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('إعدادات المهمة')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('مهمة متكررة')
                            ->helperText('تكرار المهمة في الأسابيع القادمة')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('مهمة نشطة')
                            ->helperText('تعطيل المهمة يجعلها مخفية في لوحات المتابعة')
                            ->default(true),
                            
                        // بيانات إضافية سيتم ملؤها تلقائياً
                        Forms\Components\Hidden::make('week_number')
                            ->default(function() {
                                return Carbon::now()->weekOfYear;
                            }),
                            
                        Forms\Components\Hidden::make('year')
                            ->default(function() {
                                return Carbon::now()->year;
                            }),
                            
                        Forms\Components\Hidden::make('created_by')
                            ->default(function() {
                                return Auth::id();
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان المهمة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('channel')
                    ->label('القناة')
                    ->badge()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('schedule_type')
                    ->label('نوع الجدولة')
                    ->badge()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('يوم الأسبوع')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('المسؤول')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منخفضة' => 'gray',
                        'عادية' => 'info',
                        'عالية' => 'warning',
                        'حرجة' => 'danger',
                        default => 'primary',
                    })
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('متكررة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                Tables\Columns\TextColumn::make('marketingTaskWeek.title')
                    ->label('الأسبوع')
                    ->formatStateUsing(fn ($record) => "أسبوع {$record->week_number} - {$record->year}")
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('schedule_type')
                    ->label('نوع الجدولة')
                    ->options([
                        'يومي' => 'يومي',
                        'أسبوعي' => 'أسبوعي',
                        'شهري' => 'شهري',
                        'مرة واحدة' => 'مرة واحدة',
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'منخفضة' => 'منخفضة',
                        'عادية' => 'عادية',
                        'عالية' => 'عالية',
                        'حرجة' => 'حرجة',
                    ]),
                    
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('المسؤول')
                    ->relationship('assignedUser', 'name'),
                    
                Tables\Filters\SelectFilter::make('channel')
                    ->label('القناة')
                    ->options([
                        'منصات التواصل' => 'منصات التواصل',
                        'إيميل' => 'إيميل',
                        'واتساب' => 'واتساب',
                        'إعلانات مدفوعة' => 'إعلانات مدفوعة',
                        'موقع إلكتروني' => 'موقع إلكتروني',
                        'تقارير' => 'تقارير',
                        'تخطيط' => 'تخطيط',
                        'اجتماع' => 'اجتماع',
                        'أخرى' => 'أخرى',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('المهام النشطة')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط')
                    ->placeholder('جميع المهام')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                        
                    Tables\Actions\Action::make('toggle_completion')
                        ->label(fn ($record) => $record->isCompletedForWeek() ? 'تمييز كغير مكتملة' : 'تمييز كمكتملة')
                        ->icon(fn ($record) => $record->isCompletedForWeek() ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->isCompletedForWeek() ? 'danger' : 'success')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('ملاحظات الإنجاز')
                                ->placeholder('أضف ملاحظاتك حول إنجاز المهمة')
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            $completed = !$record->isCompletedForWeek();
                            $record->markCompletedForCurrentWeek($completed, $data['notes'] ?? null);
                        }),
                        
                    Tables\Actions\Action::make('create_copy')
                        ->label('نسخ المهمة')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('target_week_id')
                                ->label('الأسبوع المستهدف')
                                ->options(function() {
                                    $currentDate = Carbon::now();
                                    $currentWeek = $currentDate->weekOfYear;
                                    $currentYear = $currentDate->year;
                                    
                                    // إحضار الأسابيع الحالية والقادمة فقط
                                    return MarketingTaskWeek::where(function($query) use ($currentYear, $currentWeek) {
                                        $query->where('year', '>', $currentYear)
                                            ->orWhere(function($query) use ($currentYear, $currentWeek) {
                                                $query->where('year', '=', $currentYear)
                                                    ->where('week_number', '>=', $currentWeek);
                                            });
                                    })
                                    ->orderBy('year')
                                    ->orderBy('week_number')
                                    ->get()
                                    ->mapWithKeys(function($week) {
                                        return [$week->id => "الأسبوع {$week->week_number} - {$week->year}"];
                                    });
                                })
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            // عمل نسخة من المهمة للأسبوع المطلوب
                            $targetWeek = MarketingTaskWeek::findOrFail($data['target_week_id']);
                            
                            $copy = $record->replicate(['completion_dates']);
                            $copy->week_number = $targetWeek->week_number;
                            $copy->year = $targetWeek->year;
                            $copy->marketing_task_week_id = $targetWeek->id;
                            $copy->save();
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('تحديد كمكتملة')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markCompletedForCurrentWeek(true);
                            }
                        }),
                        
                    Tables\Actions\BulkAction::make('mark_incomplete')
                        ->label('تحديد كغير مكتملة')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markCompletedForCurrentWeek(false);
                            }
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CompletionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingTasks::route('/'),
            'create' => Pages\CreateMarketingTask::route('/create'),
            'edit' => Pages\EditMarketingTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $now = Carbon::now();
        $weekNumber = $now->weekOfYear;
        $year = $now->year;
        
        return static::getModel()::where('is_active', true)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
