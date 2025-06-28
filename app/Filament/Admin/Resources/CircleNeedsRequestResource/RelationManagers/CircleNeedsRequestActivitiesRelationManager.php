<?php

namespace App\Filament\Admin\Resources\CircleNeedsRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CircleNeedsRequestActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'circleNeedsRequestActivities';

    protected static ?string $recordTitleAttribute = 'activity';

    // تخصيص بالعربية
    protected static ?string $title = 'أنشطة الطلب';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('activity')
                    ->label('النشاط')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('stage_id')
                    ->label('المرحلة')
                    ->relationship('stage', 'name')
                    ->required(),
                Forms\Components\TextInput::make('result')
                    ->label('النتيجة')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('activity_date')
                    ->label('تاريخ النشاط')
                    ->required(),
                Forms\Components\Toggle::make('is_completed')
                    ->label('مكتمل؟')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity')
                    ->label('النشاط')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label('المرحلة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('result')
                    ->label('النتيجة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('تاريخ النشاط')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('مكتمل؟')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('المرحلة')
                    ->relationship('stage', 'name'),
                Tables\Filters\Filter::make('is_completed')
                    ->label('المكتملة')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', true)),
                Tables\Filters\Filter::make('not_completed')
                    ->label('غير المكتملة')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', false)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة نشاط جديد'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_completed' => true]))
                    ->visible(fn ($record) => !$record->is_completed),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsCompleted')
                        ->label('تحديد كمكتملة')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_completed' => true]))
                        ->color('success')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}