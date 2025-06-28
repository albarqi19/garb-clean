<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CurriculumPlanResource\Pages;
use App\Filament\Admin\Resources\CurriculumPlanResource\RelationManagers;
use App\Models\CurriculumPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CurriculumPlanResource extends Resource
{
    protected static ?string $model = CurriculumPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'خطط المناهج';
    
    protected static ?string $modelLabel = 'خطة منهج';
    
    protected static ?string $pluralModelLabel = 'خطط المناهج';
    
    protected static ?string $navigationGroup = 'إدارة المناهج';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الخطة')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخطة')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الخطة')
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('type')
                            ->label('نوع الخطة')
                            ->options([
                                'ثلاثي' => 'منهج ثلاثي (حفظ + مراجعة صغرى + مراجعة كبرى)',
                                'ثنائي' => 'منهج ثنائي (حفظ + مراجعة)',
                                'حفظ فقط' => 'حفظ فقط',
                                'مراجعة فقط' => 'مراجعة فقط',
                            ])
                            ->default('ثلاثي')
                            ->required(),
                            
                        Forms\Components\TextInput::make('total_days')
                            ->label('إجمالي الأيام')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('إعدادات الخطة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشطة')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_template')
                            ->label('قالب')
                            ->helperText('هل هذه الخطة قالب يمكن استخدامه لإنشاء خطط أخرى؟'),
                            
                        Forms\Components\Select::make('created_by')
                            ->label('أنشئت بواسطة')
                            ->relationship('creator', 'name')                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->default(fn () => Auth::id()),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('بيانات الخطة')
                    ->schema([
                        Forms\Components\KeyValue::make('plan_data')
                            ->label('بيانات أيام الخطة')
                            ->keyLabel('اليوم')
                            ->valueLabel('الأنشطة')
                            ->addActionLabel('إضافة يوم')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Textarea::make('instructions')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('expected_days')
                    ->numeric(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الخطة')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع الخطة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ثلاثي' => 'success',
                        'ثنائي' => 'warning',
                        'حفظ فقط' => 'info',
                        'مراجعة فقط' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('total_days')
                    ->label('عدد الأيام')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('المنشئ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع الخطة')
                    ->options([
                        'ثلاثي' => 'منهج ثلاثي',
                        'ثنائي' => 'منهج ثنائي', 
                        'حفظ فقط' => 'حفظ فقط',
                        'مراجعة فقط' => 'مراجعة فقط',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشطة')
                    ->placeholder('الكل')
                    ->trueLabel('نشطة فقط')
                    ->falseLabel('غير نشطة فقط'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('duplicate')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function (CurriculumPlan $record) {
                        $newPlan = $record->replicate();
                        $newPlan->name = $record->name . ' - نسخة';
                        $newPlan->is_active = false;
                        $newPlan->created_by = Auth::id();
                        $newPlan->save();
                        
                        // نسخ أيام الخطة
                        foreach ($record->planDays as $day) {
                            $newDay = $day->replicate();
                            $newDay->curriculum_plan_id = $newPlan->id;
                            $newDay->save();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('نسخ الخطة')
                    ->modalDescription('هل أنت متأكد من نسخ هذه الخطة؟'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurriculumPlans::route('/'),
            'create' => Pages\CreateCurriculumPlan::route('/create'),
            'view' => Pages\ViewCurriculumPlan::route('/{record}'),
            'edit' => Pages\EditCurriculumPlan::route('/{record}/edit'),
        ];
    }
}
