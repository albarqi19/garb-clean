<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StrategicInitiativeResource\Pages;
use App\Filament\Admin\Resources\StrategicInitiativeResource\RelationManagers;
use App\Models\StrategicInitiative;
use App\Models\StrategicPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Filament\Components\ProgressColumn;

class StrategicInitiativeResource extends Resource
{
    protected static ?string $model = StrategicInitiative::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $modelLabel = 'مبادرة استراتيجية';
    protected static ?string $pluralModelLabel = 'المبادرات الاستراتيجية';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المبادرة الأساسية')
                    ->description('المعلومات الرئيسية للمبادرة الاستراتيجية')
                    ->schema([
                        Forms\Components\Select::make('strategic_plan_id')
                            ->label('الخطة الاستراتيجية')
                            ->options(fn () => StrategicPlan::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('strategic_indicator_id', null)),
                            
                        Forms\Components\Select::make('strategic_indicator_id')
                            ->label('المؤشر الاستراتيجي')
                            ->options(function (callable $get) {
                                $planId = $get('strategic_plan_id');
                                if (!$planId) {
                                    return [];
                                }
                                return \App\Models\StrategicIndicator::where('strategic_plan_id', $planId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المبادرة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المبادرة')
                            ->columnSpanFull()
                            ->rows(3),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية المتوقع')
                            ->required()
                            ->after('start_date'),
                            
                        Forms\Components\Select::make('status')
                            ->label('حالة المبادرة')
                            ->options([
                                'لم تبدأ' => 'لم تبدأ',
                                'قيد التنفيذ' => 'قيد التنفيذ',
                                'متوقفة' => 'متوقفة',
                                'مكتملة' => 'مكتملة',
                                'ملغاة' => 'ملغاة',
                            ])
                            ->default('لم تبدأ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('budget')
                            ->label('الميزانية التقديرية')
                            ->numeric()
                            ->prefix('ريال'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('تفاصيل المبادرة')
                    ->schema([
                        Forms\Components\Select::make('responsible_user_id')
                            ->label('المسؤول عن المبادرة')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\TagsInput::make('stakeholders')
                            ->label('الجهات المعنية')
                            ->placeholder('أضف جهة ثم اضغط Enter'),
                            
                        Forms\Components\Textarea::make('outputs')
                            ->label('المخرجات المتوقعة')
                            ->rows(2),
                            
                        Forms\Components\Textarea::make('challenges')
                            ->label('التحديات المحتملة')
                            ->rows(2),
                            
                        Forms\Components\TextInput::make('completion_percentage')
                            ->label('نسبة الإنجاز')
                            ->helperText('نسبة الإنجاز الكلية للمبادرة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                            
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المبادرة')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('strategicPlan.name')
                    ->label('الخطة الاستراتيجية')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('strategicIndicator.name')
                    ->label('المؤشر')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'لم تبدأ' => 'gray',
                        'قيد التنفيذ' => 'warning',
                        'متوقفة' => 'danger',
                        'مكتملة' => 'success',
                        'ملغاة' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('المسؤول')
                    ->sortable(),                \App\Filament\Components\ProgressColumn::make('completion_percentage')
                    ->label('نسبة الإنجاز')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date('d-m-Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->end_date && $record->end_date->isPast() && 
                        !in_array($record->status, ['مكتملة', 'ملغاة']) 
                            ? 'danger' 
                            : null
                    ),
                    
                Tables\Columns\TextColumn::make('budget')
                    ->label('الميزانية')
                    ->money('SAR')
                    ->sortable(),
                    
                // إزالة عمود is_active
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('strategic_plan_id')
                    ->label('الخطة الاستراتيجية')
                    ->relationship('strategicPlan', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'لم تبدأ' => 'لم تبدأ',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'متوقفة' => 'متوقفة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ]),
                    
                // إزالة مرشح is_active
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('المبادرات المتأخرة')
                    ->query(fn (Builder $query) => $query->where('end_date', '<', now())
                                                  ->whereNotIn('status', ['مكتملة', 'ملغاة'])),
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
                                    'لم تبدأ' => 'لم تبدأ',
                                    'قيد التنفيذ' => 'قيد التنفيذ',
                                    'متوقفة' => 'متوقفة',
                                    'مكتملة' => 'مكتملة',
                                    'ملغاة' => 'ملغاة',
                                ])
                                ->required(),
                                
                            Forms\Components\TextInput::make('completion_percentage')
                                ->label('نسبة الإنجاز')
                                ->helperText('نسبة الإنجاز الكلية للمبادرة')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%'),
                                
                            Forms\Components\Textarea::make('comment')
                                ->label('ملاحظات')
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            $record->status = $data['status'];
                            if (isset($data['completion_percentage'])) {
                                $record->completion_percentage = $data['completion_percentage'];
                            }
                            if ($data['status'] === 'مكتملة') {
                                $record->completion_percentage = 100;
                            }
                            $record->save();
                        }),
                        
                    Tables\Actions\Action::make('add_tasks')
                        ->label('ربط بمهام')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('success')
                        ->url(fn ($record) => route('admin.tasks.index', ['strategic_initiative_id' => $record->id]))
                        ->openUrlInNewTab(),
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
                                    'لم تبدأ' => 'لم تبدأ',
                                    'قيد التنفيذ' => 'قيد التنفيذ',
                                    'متوقفة' => 'متوقفة',
                                    'مكتملة' => 'مكتملة',
                                    'ملغاة' => 'ملغاة',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->status = $data['status'];
                                if ($data['status'] === 'مكتملة') {
                                    $record->completion_percentage = 100;
                                }
                                $record->save();
                            }
                        }),
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
            'index' => Pages\ListStrategicInitiatives::route('/'),
            'create' => Pages\CreateStrategicInitiative::route('/create'),
            'edit' => Pages\EditStrategicInitiative::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['لم تبدأ', 'قيد التنفيذ'])
            ->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::whereIn('status', ['لم تبدأ', 'قيد التنفيذ'])
            ->count();
        
        if ($count > 0) {
            return 'warning';
        }
        
        return 'success';
    }
}
