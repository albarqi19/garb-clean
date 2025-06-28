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

class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';
    
    // تعريب مدير العلاقة
    protected static ?string $title = 'سجل تغييرات الحالة';
    protected static ?string $modelLabel = 'سجل تغيير حالة';
    protected static ?string $pluralModelLabel = 'سجل تغييرات الحالة';
    
    // منع التعديل والإضافة في سجل التغييرات
    protected static bool $canCreate = false;
    protected static bool $canEdit = false;
    protected static bool $canDelete = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_status')
                    ->label('الحالة السابقة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ])
                    ->disabled(),
                    
                Forms\Components\Select::make('to_status')
                    ->label('الحالة الجديدة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ])
                    ->disabled(),
                    
                Forms\Components\Textarea::make('comment')
                    ->label('ملاحظات التغيير')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('completion_percentage')
                    ->label('نسبة الإنجاز')
                    ->numeric()
                    ->disabled(),
                    
                Forms\Components\Select::make('user_id')
                    ->label('تم التغيير بواسطة')
                    ->relationship('user', 'name')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                Tables\Columns\TextColumn::make('from_status')
                    ->label('الحالة السابقة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'جديدة' => 'info',
                        'قيد التنفيذ' => 'warning',
                        'معلقة' => 'gray',
                        'مكتملة' => 'success',
                        'ملغاة' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('to_status')
                    ->label('الحالة الجديدة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'جديدة' => 'info',
                        'قيد التنفيذ' => 'warning',
                        'معلقة' => 'gray',
                        'مكتملة' => 'success',
                        'ملغاة' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('comment')
                    ->label('ملاحظات')
                    ->wrap()
                    ->limit(100),
                    
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('نسبة الإنجاز')
                    ->suffix('%'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('تم بواسطة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التغيير')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}