<?php

namespace App\Filament\Admin\Resources\StrategicPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class IndicatorsRelationManager extends RelationManager
{
    protected static string $relationship = 'indicators';
    
    // تعريب مدير العلاقة
    protected static ?string $title = 'المؤشرات الاستراتيجية';
    protected static ?string $modelLabel = 'مؤشر استراتيجي';
    protected static ?string $pluralModelLabel = 'المؤشرات الاستراتيجية';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    
                Forms\Components\Select::make('responsible_user_id')
                    ->label('المسؤول عن المؤشر')
                    ->relationship('responsibleUser', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Forms\Components\TextInput::make('measurement_method')
                    ->label('طريقة القياس')
                    ->maxLength(255),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                    
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Auth::id())
                    ->dehydrated()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المؤشر')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),
                    
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
                    ->label('الإنجاز الحالي')
                    ->formatStateUsing(function ($record) {
                        $monitorings = $record->monitorings()->where('year', date('Y'))->get();
                        if ($monitorings->isEmpty()) {
                            return '0%';
                        }
                        
                        return number_format($monitorings->avg('achievement_percentage'), 1) . '%';
                    }),
                    
                Tables\Columns\TextColumn::make('monitorings_count')
                    ->label('عمليات الرصد')
                    ->counts('monitorings')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('المسؤول')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('المؤشرات النشطة')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط')
                    ->placeholder('جميع المؤشرات')
                    ->default(true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة مؤشر'),
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
                        
                    Tables\Actions\Action::make('add_monitoring')
                        ->label('إضافة عملية رصد')
                        ->icon('heroicon-o-chart-bar-square')
                        ->color('primary')
                        ->url(fn ($record) => route('admin.strategic-indicators.edit', $record))
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}