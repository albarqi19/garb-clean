<?php

namespace App\Filament\Admin\Resources\MosqueResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranCirclesRelationManager extends RelationManager
{
    protected static string $relationship = 'quranCircles';
    protected static ?string $title = 'الحلقات القرآنية';
    protected static ?string $label = 'حلقة قرآنية';
    protected static ?string $pluralLabel = 'الحلقات القرآنية';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الحلقة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('circle_type')
                    ->label('نوع الحلقة')
                    ->required()
                    ->options([
                        'حلقة فردية' => 'حلقة فردية',
                        'حلقة جماعية' => 'حلقة جماعية',
                    ]),
                Forms\Components\Select::make('circle_status')
                    ->label('حالة الحلقة')
                    ->required()
                    ->options([
                        'نشطة' => 'نشطة',
                        'معلقة' => 'معلقة',
                        'مغلقة' => 'مغلقة',
                    ]),
                Forms\Components\Select::make('time_period')
                    ->label('الفترة الزمنية')
                    ->required()
                    ->options([
                        'عصر' => 'عصر',
                        'مغرب' => 'مغرب',
                        'عصر ومغرب' => 'عصر ومغرب',
                        'كل الأوقات' => 'كل الأوقات',
                        'صباحية' => 'صباحية',
                        'مسائية' => 'مسائية',
                        'ليلية' => 'ليلية',
                        'الفجر' => 'الفجر',
                    ]),
                Forms\Components\TextInput::make('registration_link')
                    ->label('رابط التسجيل')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Toggle::make('has_ratel')
                    ->label('مفعل في برنامج رتل')
                    ->default(false),
                Forms\Components\Toggle::make('has_qias')
                    ->label('يستخدم نظام قياس')
                    ->default(false),
                Forms\Components\TextInput::make('masser_link')
                    ->label('رابط ماسر')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Select::make('supervisor_id')
                    ->label('المشرف المسؤول')
                    ->relationship('supervisor', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('monitor_id')
                    ->label('المراقب المسؤول')
                    ->relationship('monitor', 'name')
                    ->searchable()
                    ->preload(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_type')
                    ->label('نوع الحلقة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'حلقة فردية' => 'success',
                        'حلقة جماعية' => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_status')
                    ->label('حالة الحلقة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'نشطة' => 'success',
                        'معلقة' => 'warning',
                        'مغلقة' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_period')
                    ->label('الفترة')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_ratel')
                    ->label('رتل')
                    ->boolean(),
                Tables\Columns\IconColumn::make('has_qias')
                    ->label('قياس')
                    ->boolean(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->visible(fn ($record) => $record !== null && $record->circle_type === 'حلقة فردية'),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('المشرف')
                    ->sortable(),
                Tables\Columns\TextColumn::make('activeTasks_count')
                    ->label('المهام النشطة')
                    ->counts('activeTasks')
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'حلقة فردية' => 'حلقة فردية',
                        'حلقة جماعية' => 'حلقة جماعية',
                    ]),
                Tables\Filters\SelectFilter::make('circle_status')
                    ->label('حالة الحلقة')
                    ->options([
                        'نشطة' => 'نشطة',
                        'معلقة' => 'معلقة',
                        'مغلقة' => 'مغلقة',
                    ]),
                Tables\Filters\SelectFilter::make('time_period')
                    ->label('الفترة الزمنية')
                    ->options([
                        'عصر' => 'عصر',
                        'مغرب' => 'مغرب',
                        'عصر ومغرب' => 'عصر ومغرب',
                        'كل الأوقات' => 'كل الأوقات',
                        'صباحية' => 'صباحية',
                        'مسائية' => 'مسائية',
                        'ليلية' => 'ليلية',
                        'الفجر' => 'الفجر',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إنشاء حلقة جديدة'),
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
