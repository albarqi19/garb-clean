<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WhatsAppTemplateResource\Pages;
use App\Filament\Admin\Resources\WhatsAppTemplateResource\RelationManagers;
use App\Models\WhatsAppTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('template_key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('template_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('template_content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('variables'),
                Forms\Components\TextInput::make('category')
                    ->required()
                    ->maxLength(255)
                    ->default('general'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('template_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('template_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppTemplates::route('/'),
            'create' => Pages\CreateWhatsAppTemplate::route('/create'),
            'edit' => Pages\EditWhatsAppTemplate::route('/{record}/edit'),
        ];
    }
}
