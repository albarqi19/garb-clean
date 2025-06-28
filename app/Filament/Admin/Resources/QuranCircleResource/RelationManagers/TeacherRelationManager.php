<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherRelationManager extends RelationManager
{
    protected static string $relationship = 'activeTeachers';
    protected static ?string $title = 'معلمين الحلقة';
    protected static ?string $label = 'معلم';
    protected static ?string $pluralLabel = 'المعلمين';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المعلم')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الجوال')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Select::make('nationality')
                    ->label('الجنسية')
                    ->options([
                        'سعودي' => 'سعودي',
                        'مصري' => 'مصري',
                        'سوداني' => 'سوداني',
                        'يمني' => 'يمني',
                        'سوري' => 'سوري',
                        'أردني' => 'أردني',
                        'فلسطيني' => 'فلسطيني',
                        'باكستاني' => 'باكستاني',
                        'هندي' => 'هندي',
                        'بنجلاديشي' => 'بنجلاديشي',
                        'أخرى' => 'أخرى',
                    ]),
                Forms\Components\TextInput::make('national_id')
                    ->label('رقم الهوية')
                    ->maxLength(20),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('الجنسية'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nationality')
                    ->label('تصفية حسب الجنسية')
                    ->options([
                        'سعودي' => 'سعودي',
                        'مصري' => 'مصري',
                        'سوداني' => 'سوداني',
                        'يمني' => 'يمني',
                        'سوري' => 'سوري',
                        'أردني' => 'أردني',
                        'فلسطيني' => 'فلسطيني',
                        'باكستاني' => 'باكستاني',
                        'هندي' => 'هندي',
                        'بنجلاديشي' => 'بنجلاديشي',
                        'أخرى' => 'أخرى',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة معلم'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }
}
