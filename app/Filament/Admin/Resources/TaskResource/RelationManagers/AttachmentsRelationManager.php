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
use Filament\Forms\Components\FileUpload;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';
    
    // تعريب مدير العلاقة
    protected static ?string $title = 'المرفقات';
    protected static ?string $modelLabel = 'مرفق';
    protected static ?string $pluralModelLabel = 'المرفقات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المرفق')
                    ->required()
                    ->maxLength(255),
                    
                FileUpload::make('file_path')
                    ->label('الملف المرفق')
                    ->disk('public')
                    ->directory('task-attachments')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'application/zip'])
                    ->maxSize(10240) // 10 ميجابايت
                    ->required(),
                    
                Forms\Components\TextInput::make('description')
                    ->label('وصف المرفق')
                    ->maxLength(255),
                    
                Forms\Components\Toggle::make('is_visible_to_external')
                    ->label('مرئي للمستخدمين الخارجيين')
                    ->helperText('هل المرفق مرئي للطلاب والمعلمين؟')
                    ->default(false),
                    
                Forms\Components\Hidden::make('uploaded_by')
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
                    ->label('اسم المرفق')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_visible_to_external')
                    ->label('مرئي للخارج')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('uploaded_by_user.name')
                    ->label('تم الرفع بواسطة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_visible_to_external')
                    ->label('المرفقات المرئية للخارج فقط')
                    ->query(fn (Builder $query) => $query->where('is_visible_to_external', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة مرفق'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->visible(fn ($record) => $record->uploaded_by === Auth::id() || Auth::user()->hasRole('admin')),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn ($record) => $record->uploaded_by === Auth::id() || Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}