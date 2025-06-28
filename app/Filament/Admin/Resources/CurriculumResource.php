<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CurriculumResource\Pages;
use App\Filament\Admin\Resources\CurriculumResource\RelationManagers;
use App\Models\Curriculum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurriculumResource extends Resource
{
    protected static ?string $model = Curriculum::class;

    // تعيين أيقونة مناسبة للمناهج
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'منهج تعليمي';
    protected static ?string $pluralModelLabel = 'المناهج التعليمية';
      // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المناهج والخطط الدراسية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المنهج')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المنهج')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('نوع المنهج')
                            ->options([
                                'منهج تلقين' => 'منهج تلقين',
                                'منهج طالب' => 'منهج طالب',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المنهج')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعّال')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المنهج')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع المنهج')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منهج تلقين' => 'primary',
                        'منهج طالب' => 'success',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('levels_count')
                    ->label('عدد المستويات')
                    ->counts('levels'),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع المنهج')
                    ->options([
                        'منهج تلقين' => 'منهج تلقين',
                        'منهج طالب' => 'منهج طالب',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('فعّال')
                    ->trueLabel('المناهج الفعّالة فقط')
                    ->falseLabel('المناهج غير الفعّالة فقط')
                    ->native(false)
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
      public static function getRelations(): array
    {
        return [
            RelationManagers\LevelsRelationManager::class,
            RelationManagers\PlansRelationManager::class,
            RelationManagers\StudentProgressRelationManager::class,
        ];
    }public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurricula::route('/'),
            'create' => Pages\CreateCurriculum::route('/create'),
            'edit' => Pages\EditCurriculum::route('/{record}/edit'),
            // تم تعطيل هذه الصفحة مؤقتًا لإصلاح المشكلة
            // 'create-bulk-plans' => Pages\CreateBulkPlans::route('/{record}/create-bulk-plans'),
        ];
    }

    /**
     * إظهار عدد المناهج في مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    /**
     * تحديد لون مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
