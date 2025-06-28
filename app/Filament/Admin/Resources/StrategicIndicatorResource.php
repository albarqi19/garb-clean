<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StrategicIndicatorResource\Pages;
use App\Filament\Admin\Resources\StrategicIndicatorResource\RelationManagers;
use App\Models\StrategicIndicator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StrategicIndicatorResource extends Resource
{
    protected static ?string $model = StrategicIndicator::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $modelLabel = 'مؤشر استراتيجي';
    protected static ?string $pluralModelLabel = 'المؤشرات الاستراتيجية';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المؤشر الأساسية')
                    ->description('المعلومات الرئيسية للمؤشر الاستراتيجي')
                    ->schema([
                        Forms\Components\Select::make('strategic_plan_id')
                            ->label('الخطة الاستراتيجية')
                            ->relationship('strategicPlan', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المؤشر')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المؤشر')
                            ->columnSpanFull()
                            ->rows(3),
                            
                        Forms\Components\TextInput::make('unit')
                            ->label('وحدة القياس')
                            ->placeholder('مثال: نسبة مئوية، عدد، ساعة')
                            ->required()
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('target_value')
                            ->label('القيمة المستهدفة')
                            ->required()
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('weight')
                            ->label('الوزن النسبي للمؤشر')
                            ->helperText('وزن المؤشر في الخطة الإستراتيجية (النسبة المئوية من إجمالي الخطة)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(10)
                            ->suffix('%'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('معلومات المسؤولية والقياس')
                    ->description('بيانات المسؤولية عن المؤشر وطريقة القياس')
                    ->schema([
                        Forms\Components\Select::make('responsible_user_id')
                            ->label('المسؤول عن المؤشر')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\TextInput::make('measurement_method')
                            ->label('طريقة القياس')
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                            
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
                    ->label('اسم المؤشر')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('strategicPlan.name')
                    ->label('الخطة الاستراتيجية')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('unit')
                    ->label('وحدة القياس'),
                    
                Tables\Columns\TextColumn::make('target_value')
                    ->label('القيمة المستهدفة')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('weight')
                    ->label('الوزن النسبي')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_achievement')
                    ->label('نسبة الإنجاز')
                    ->formatStateUsing(function ($record) {
                        $currentYear = date('Y');
                        $monitorings = $record->monitorings()->where('year', $currentYear)->get();
                        
                        if ($monitorings->isEmpty()) {
                            return '0%';
                        }
                        
                        return number_format($monitorings->avg('achievement_percentage'), 1) . '%';
                    }),
                    
                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('المسؤول')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('strategic_plan_id')
                    ->label('الخطة الاستراتيجية')
                    ->relationship('strategicPlan', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('المؤشرات النشطة')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط')
                    ->placeholder('جميع المؤشرات')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'إلغاء تنشيط' : 'تنشيط')
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
            RelationManagers\MonitoringsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicIndicators::route('/'),
            'create' => Pages\CreateStrategicIndicator::route('/create'),
            'edit' => Pages\EditStrategicIndicator::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
