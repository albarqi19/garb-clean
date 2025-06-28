<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StrategicPlanResource\Pages;
use App\Filament\Admin\Resources\StrategicPlanResource\RelationManagers;
use App\Models\StrategicPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StrategicPlanResource extends Resource
{
    protected static ?string $model = StrategicPlan::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $modelLabel = 'خطة استراتيجية';
    protected static ?string $pluralModelLabel = 'الخطط الاستراتيجية';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الخطة الاستراتيجية')
                    ->description('البيانات الأساسية للخطة الاستراتيجية')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخطة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الخطة')
                            ->columnSpanFull()
                            ->rows(3),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->required()
                            ->default(now()->addYears(3))
                            ->afterOrEqual('start_date'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('الخطة نشطة')
                            ->default(true)
                            ->helperText('تحديد ما إذا كانت الخطة نشطة حالياً أم لا'),
                            
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id())
                            ->dehydrated()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الخطة')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('d-m-Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('d-m-Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('indicators_count')
                    ->label('عدد المؤشرات')
                    ->counts('indicators'),
                    
                Tables\Columns\TextColumn::make('current_achievement')
                    ->label('نسبة الإنجاز الحالية')
                    ->formatStateUsing(fn ($record) => number_format($record->calculateAchievementPercentage(), 1) . '%')
                    ->description(fn () => 'للسنة الحالية: ' . date('Y')),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('أنشئت بواسطة')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الخطط النشطة')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط')
                    ->placeholder('جميع الخطط')
                    ->default(true),
                    
                Tables\Filters\Filter::make('current_plans')
                    ->label('الخطط الحالية')
                    ->query(fn (Builder $query) => $query->where('start_date', '<=', now())
                                                   ->where('end_date', '>=', now())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'إلغاء تنشيط الخطة' : 'تنشيط الخطة')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->is_active = !$record->is_active;
                            $record->save();
                        }),
                ]),
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
            RelationManagers\IndicatorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicPlans::route('/'),
            'create' => Pages\CreateStrategicPlan::route('/create'),
            'edit' => Pages\EditStrategicPlan::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->exists() ? 'success' : 'gray';
    }
}
