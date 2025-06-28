<?php

namespace App\Filament\Admin\Resources\MarketingTaskWeekResource\RelationManagers;

use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    // تعريب العناوين
    protected static ?string $title = 'المهام التسويقية';
    protected static ?string $modelLabel = 'مهمة تسويقية';
    protected static ?string $pluralModelLabel = 'المهام التسويقية';

    public function form(Form $form): Form
    {
        $weekModel = $this->getOwnerRecord();

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
                            
                        // إخفاء حقل أسبوع المهمة واستخدام الأسبوع الحالي تلقائياً
                        Forms\Components\Hidden::make('marketing_task_week_id')
                            ->default(function () use ($weekModel) {
                                return $weekModel->id;
                            }),
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
                            
                        // بيانات إضافية سيتم ملؤها تلقائياً من الأسبوع الحالي
                        Forms\Components\Hidden::make('week_number')
                            ->default(function () use ($weekModel) {
                                return $weekModel->week_number;
                            }),
                            
                        Forms\Components\Hidden::make('year')
                            ->default(function () use ($weekModel) {
                                return $weekModel->year;
                            }),
                            
                        Forms\Components\Hidden::make('created_by')
                            ->default(function() {
                                return Auth::id();
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
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
                    
                // إضافة عمود حالة الإنجاز
                Tables\Columns\IconColumn::make('completion_status')
                    ->label('الإنجاز')
                    ->getStateUsing(function (Model $record) {
                        return $record->isCompletedForWeek();
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('متكررة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
            ])
            ->filters([
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة مهمة تسويقية'),
                
                // إضافة إجراء لإنشاء المهام الافتراضية للأسبوع
                Tables\Actions\Action::make('create_default_tasks')
                    ->label('إنشاء المهام الافتراضية')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('success')
                    ->action(function () {
                        $weekModel = $this->getOwnerRecord();
                        
                        // استدعاء الدالة التي تقوم بإنشاء المهام الافتراضية
                        \App\Models\MarketingTask::createDefaultTasks(
                            $weekModel->week_number, 
                            $weekModel->year, 
                            Auth::id()
                        );
                    })
                    ->requiresConfirmation()
                    ->modalHeading('إنشاء المهام الافتراضية للأسبوع')
                    ->modalDescription('هل أنت متأكد من إنشاء المهام التسويقية الافتراضية لهذا الأسبوع؟ سيتم إضافة المهام الرئيسية التي تتكرر أسبوعياً.')
                    ->modalSubmitActionLabel('نعم، إنشاء المهام'),
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
                        ->url(fn ($record) => route('filament.admin.resources.marketing-tasks.edit', $record))
                        ->openUrlInNewTab(),
                        
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
            ->defaultSort('priority', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('marketing_task_week_id', $this->getOwnerRecord()->id));
    }
}
