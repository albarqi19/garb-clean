<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskStatusResource\Pages;
use App\Models\TaskStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskStatusResource extends Resource
{
    protected static ?string $model = TaskStatus::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $modelLabel = 'سجل حالة مهمة';
    protected static ?string $pluralModelLabel = 'سجل حالات المهام';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 12;

    // إخفاء المورد من القائمة الرئيسية وجعله مرئي فقط من خلال علاقته بـالمهمة
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task_id')
                    ->label('المهمة')
                    ->relationship('task', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('from_status')
                    ->label('الحالة السابقة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('to_status')
                    ->label('الحالة الجديدة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('comment')
                    ->label('ملاحظات التغيير')
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('completion_percentage')
                    ->label('نسبة الإنجاز')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('عنوان المهمة')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                    
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
                    ->limit(100)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
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
                Tables\Filters\SelectFilter::make('to_status')
                    ->label('الحالة الجديدة')
                    ->options([
                        'جديدة' => 'جديدة',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'معلقة' => 'معلقة',
                        'مكتملة' => 'مكتملة',
                        'ملغاة' => 'ملغاة',
                    ]),
                    
                Tables\Filters\Filter::make('is_completion')
                    ->label('تغييرات الإكمال فقط')
                    ->query(fn (Builder $query) => $query->where('to_status', 'مكتملة')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // لا نحتاج لإجراءات جماعية لسجل الحالات
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTaskStatuses::route('/'),
        ];
    }
}
