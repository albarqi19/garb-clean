<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Filament\Admin\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'مهمة';
    protected static ?string $pluralModelLabel = 'المهام';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المهمة الأساسية')
                    ->description('تفاصيل المهمة الرئيسية والمواعيد')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المهمة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\RichEditor::make('description')
                            ->label('وصف المهمة')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'bulletList', 
                                'orderedList', 'redo', 'undo',
                            ])
                            ->columnSpanFull(),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->required()
                            ->default(now()->addDays(7))
                            ->afterOrEqual('start_date')
                            ->helperText(function ($state) {
                                if ($state) {
                                    $dueDate = Carbon::parse($state);
                                    $now = Carbon::now();
                                    $diff = $now->diffInDays($dueDate, false);
                                    
                                    if ($diff < 0) {
                                        return 'متأخر بـ ' . abs($diff) . ' يوم';
                                    } elseif ($diff == 0) {
                                        return 'اليوم هو آخر موعد';
                                    } else {
                                        return 'متبقي ' . $diff . ' يوم';
                                    }
                                }
                                
                                return '';
                            }),
                    ]),
                    
                Forms\Components\Section::make('تفاصيل وحالة المهمة')
                    ->description('حالة المهمة والقسم والأولوية')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
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
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'جديدة' => 'جديدة',
                                'قيد التنفيذ' => 'قيد التنفيذ',
                                'معلقة' => 'معلقة',
                                'مكتملة' => 'مكتملة',
                                'ملغاة' => 'ملغاة',
                            ])
                            ->default('جديدة')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'مكتملة') {
                                    $set('completion_percentage', 100);
                                    $set('completed_at', now()->format('Y-m-d'));
                                }
                            }),
                            
                        Forms\Components\Select::make('department')
                            ->label('القسم')
                            ->options([
                                'التعليمية' => 'التعليمية',
                                'المالية' => 'المالية',
                                'التسويق' => 'التسويق',
                                'الإدارية' => 'الإدارية',
                                'إدارة النظام' => 'إدارة النظام',
                                'عام' => 'عام',
                            ])
                            ->required()
                            ->default('عام'),
                        
                        Forms\Components\RangeInput::make('completion_percentage')
                            ->label('نسبة الإنجاز')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(5)
                            ->default(0)
                            ->reactive()
                            ->suffix('%')
                            ->afterStateUpdated(function ($state, callable $set, $record) {
                                if ($state == 100 && (!$record || $record->status !== 'مكتملة')) {
                                    $set('status', 'مكتملة');
                                    $set('completed_at', now()->format('Y-m-d'));
                                }
                            }),
                    ]),
                    
                Forms\Components\Section::make('المسؤوليات')
                    ->description('إسناد المهمة والمسؤول عن إنشائها')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Select::make('created_by')
                            ->label('منشئ المهمة')
                            ->relationship('creator', 'name')
                            ->default(fn () => Auth::id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                            
                        Forms\Components\Select::make('assigned_to')
                            ->label('تم إسناد المهمة إلى')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\DatePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->disabled(fn ($get) => $get('status') !== 'مكتملة')
                            ->dehydrated(),
                    ]),
                    
                Forms\Components\Section::make('خصائص المهمة')
                    ->description('خصائص وإعدادات إضافية للمهمة')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('الوسوم')
                            ->placeholder('أضف وسمًا ثم اضغط إدخال')
                            ->helperText('أضف وسوم لتصنيف المهمة'),
                            
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('مهمة متكررة')
                            ->default(false)
                            ->reactive()
                            ->helperText('حدد إذا كانت المهمة تتكرر بشكل دوري')
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(1)
                            ->visible(fn ($get) => $get('is_recurring'))
                            ->schema([
                                Forms\Components\Select::make('recurring_pattern.frequency')
                                    ->label('التكرار')
                                    ->options([
                                        'daily' => 'يومي',
                                        'weekly' => 'أسبوعي',
                                        'monthly' => 'شهري',
                                    ])
                                    ->default('weekly')
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('recurring_pattern.interval')
                                    ->label('الفاصل الزمني')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->suffix(function ($get) {
                                        $frequency = data_get($get('recurring_pattern'), 'frequency');
                                        return match ($frequency) {
                                            'daily' => ' يوم',
                                            'weekly' => ' أسبوع',
                                            'monthly' => ' شهر',
                                            default => '',
                                        };
                                    }),
                            ]),
                            
                        Forms\Components\Select::make('taskable_type')
                            ->label('نوع العنصر المرتبط')
                            ->options([
                                'App\Models\StrategicInitiative' => 'مبادرة استراتيجية',
                                'App\Models\MarketingActivity' => 'نشاط تسويقي',
                                'App\Models\CircleOpeningRequest' => 'طلب فتح حلقة',
                                'App\Models\CircleNeedsRequest' => 'طلب احتياج حلقة',
                                'App\Models\TransferRequest' => 'طلب نقل',
                            ])
                            ->searchable()
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('taskable_id')
                            ->label('معرف العنصر المرتبط')
                            ->numeric()
                            ->visible(fn ($get) => !empty($get('taskable_type'))),
                    ]),
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
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منخفضة' => 'gray',
                        'عادية' => 'primary',
                        'عالية' => 'warning',
                        'حرجة' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'جديدة' => 'info',
                        'قيد التنفيذ' => 'warning',
                        'معلقة' => 'danger',
                        'مكتملة' => 'success',
                        'ملغاة' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('department')
                    ->label('القسم')
                    ->badge()
                    ->sortable()
                    ->color('secondary'),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d-m-Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->due_date && $record->due_date->isPast() && 
                        !in_array($record->status, ['مكتملة', 'ملغاة']) 
                            ? 'danger' 
                            : null
                    ),
                    
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('مسند إلى')
                    ->sortable()
                    ->default('-'),
                    
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('الإنجاز')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => "{$state}%")
                    ->color(fn (int $state): string => match(true) {
                        $state >= 100 => 'success',
                        $state >= 70 => 'warning',
                        $state >= 30 => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (int $state): string => match(true) {
                        $state >= 100 => 'heroicon-o-check-circle',
                        default => 'heroicon-o-chart-bar',
                    }),
                    
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('متكررة')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ]),
                    
                Tables\Filters\SelectFilter::make('department')
                    ->label('القسم')
                    ->options([
                        'التعليمية' => 'التعليمية',
                        'المالية' => 'المالية',
                        'التسويق' => 'التسويق',
                        'الإدارية' => 'الإدارية',
                        'إدارة النظام' => 'إدارة النظام',
                        'عام' => 'عام',
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'منخفضة' => 'منخفضة',
                        'عادية' => 'عادية',
                        'عالية' => 'عالية',
                        'حرجة' => 'حرجة',
                    ]),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('المهام المتأخرة')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now())->whereNotIn('status', ['مكتملة', 'ملغاة'])),
                    
                Tables\Filters\Filter::make('thisWeek')
                    ->label('مهام هذا الأسبوع')
                    ->query(fn (Builder $query) => $query->whereBetween('due_date', [now(), now()->addDays(7)])->whereNotIn('status', ['مكتملة', 'ملغاة'])),
                    
                Tables\Filters\Filter::make('assigned_to_me')
                    ->label('المسندة إلي')
                    ->query(fn (Builder $query) => $query->where('assigned_to', Auth::id())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('update_status')
                        ->label('تحديث الحالة')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('الحالة')
                                ->options([
                                    'جديدة' => 'جديدة',
                                    'قيد التنفيذ' => 'قيد التنفيذ',
                                    'معلقة' => 'معلقة',
                                    'مكتملة' => 'مكتملة',
                                    'ملغاة' => 'ملغاة',
                                ])
                                ->required(),
                                
                            Forms\Components\RangeInput::make('completion_percentage')
                                ->label('نسبة الإنجاز')
                                ->integer()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(5)
                                ->reactive()
                                ->suffix('%'),
                                
                            Forms\Components\Textarea::make('comment')
                                ->label('تعليق')
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            // تحديث حالة المهمة ونسبة الإنجاز
                            $record->changeStatus(
                                $data['status'],
                                $data['comment'] ?? null,
                                $data['completion_percentage'] ?? null
                            );
                            
                            // إذا كانت الحالة مكتملة، نضبط نسبة الإنجاز على 100%
                            if ($data['status'] === 'مكتملة') {
                                $record->completion_percentage = 100;
                                $record->completed_at = now();
                                $record->save();
                            }
                        }),
                        
                    Tables\Actions\Action::make('reassign')
                        ->label('إعادة تعيين')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('تعيين المهمة إلى')
                                ->relationship('assignee', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                                
                            Forms\Components\Textarea::make('comment')
                                ->label('تعليق')
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            // تعيين المهمة للمستخدم المحدد
                            $record->assignTo($data['assigned_to']);
                            
                            // إضافة تعليق إذا تم إدخاله
                            if (!empty($data['comment'])) {
                                $record->addComment("تم إعادة تعيين المهمة: " . $data['comment']);
                            }
                        }),
                        
                    Tables\Actions\Action::make('add_comment')
                        ->label('إضافة تعليق')
                        ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                        ->color('primary')
                        ->form([
                            Forms\Components\RichEditor::make('content')
                                ->label('محتوى التعليق')
                                ->required()
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike', 'bulletList', 
                                    'orderedList', 'redo', 'undo',
                                ]),
                                
                            Forms\Components\Toggle::make('is_internal')
                                ->label('تعليق داخلي')
                                ->helperText('التعليق الداخلي يظهر فقط للإداريين والمشرفين'),
                                
                            Forms\Components\Toggle::make('is_action_item')
                                ->label('إجراء مطلوب')
                                ->helperText('حدد هذا الخيار إذا كان التعليق يتضمن إجراءً يجب تنفيذه'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->addComment(
                                $data['content'],
                                $data['is_internal'] ?? false,
                                $data['is_action_item'] ?? false
                            );
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('update_bulk_status')
                        ->label('تحديث الحالة للمحدد')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('الحالة')
                                ->options([
                                    'جديدة' => 'جديدة',
                                    'قيد التنفيذ' => 'قيد التنفيذ',
                                    'معلقة' => 'معلقة',
                                    'مكتملة' => 'مكتملة',
                                    'ملغاة' => 'ملغاة',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->changeStatus($data['status']);
                                
                                if ($data['status'] === 'مكتملة') {
                                    $record->completion_percentage = 100;
                                    $record->completed_at = now();
                                    $record->save();
                                }
                            }
                        }),
                        
                    Tables\Actions\BulkAction::make('reassign_bulk')
                        ->label('إعادة تعيين المحدد')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('تعيين المهام إلى')
                                ->relationship('assignee', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->assignTo($data['assigned_to']);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\StatusHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotIn('status', ['مكتملة', 'ملغاة'])->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = static::getModel()::where('due_date', '<', now())
            ->whereNotIn('status', ['مكتملة', 'ملغاة'])
            ->count();
            
        return $overdueCount > 0 ? 'danger' : 'primary';
    }
}
