<?php

namespace App\Filament\Admin\Resources\TaskResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    
    // تعريب مدير العلاقة
    protected static ?string $title = 'التعليقات';
    protected static ?string $modelLabel = 'تعليق';
    protected static ?string $pluralModelLabel = 'التعليقات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->label('المحتوى')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_internal')
                    ->label('تعليق داخلي')
                    ->helperText('تعليق داخلي لا يظهر للطلاب والمعلمين')
                    ->default(false),
                Forms\Components\Toggle::make('is_action_item')
                    ->label('إجراء مطلوب')
                    ->helperText('تحديد هذا التعليق كإجراء مطلوب تنفيذه')
                    ->default(false),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label('المحتوى')
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('الكاتب')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('داخلي')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_action_item')
                    ->label('إجراء مطلوب')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_action_item')
                    ->label('الإجراءات المطلوبة فقط')
                    ->query(fn (Builder $query) => $query->where('is_action_item', true)),
                Tables\Filters\Filter::make('is_internal')
                    ->label('التعليقات الداخلية فقط')
                    ->query(fn (Builder $query) => $query->where('is_internal', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة تعليق'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->hasRole('admin')),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}