<?php

namespace App\Filament\Admin\Resources\CurriculumResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LevelsRelationManager extends RelationManager
{
    protected static string $relationship = 'levels';
    
    protected static ?string $title = 'مستويات المنهج';
    protected static ?string $label = 'مستوى';
    protected static ?string $pluralLabel = 'المستويات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المستوى')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('level_order')
                    ->label('ترتيب المستوى')
                    ->numeric()
                    ->default(fn ($record) => $record?->level_order ?? $this->ownerRecord->levels->count() + 1)
                    ->minValue(1)
                    ->maxValue(4)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('وصف المستوى')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('فعّال')
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level_order')
                    ->label('الترتيب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المستوى')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plans_count')
                    ->label('عدد الخطط')
                    ->counts('plans'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('level_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('فعّال')
                    ->trueLabel('المستويات الفعّالة فقط')
                    ->falseLabel('المستويات غير الفعّالة فقط')
                    ->native(false)
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
