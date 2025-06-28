<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    // تغيير أيقونة القائمة
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    // إعداد اسم القسم في القائمة باللغة العربية
    protected static ?string $navigationLabel = 'سجل النشاط';
    
    // ترتيب القسم في القائمة
    protected static ?int $navigationSort = 90;
    
    // وصف القسم
    protected static ?string $navigationGroup = 'النظام والإدارة';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل النشاط')
                    ->schema([
                        Forms\Components\TextInput::make('user_name')
                            ->label('اسم المستخدم')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('activity_type')
                            ->label('نوع النشاط')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('module')
                            ->label('القسم/الوحدة')
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('تاريخ ووقت النشاط')
                            ->displayFormat('d-m-Y H:i:s')
                            ->disabled(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف النشاط')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('البيانات المتغيرة')
                    ->schema([
                        Forms\Components\KeyValue::make('old_values')
                            ->label('القيم القديمة')
                            ->disabled(),
                            
                        Forms\Components\KeyValue::make('new_values')
                            ->label('القيم الجديدة')
                            ->disabled(),
                    ]),
                    
                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('عنوان IP')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('user_agent')
                            ->label('متصفح المستخدم')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('user_name')
                    ->label('المستخدم')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('activity_type')
                    ->label('نوع النشاط')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'إضافة' => 'success',
                        'تعديل' => 'warning',
                        'حذف' => 'danger',
                        'تسجيل دخول' => 'info',
                        'تسجيل خروج' => 'gray',
                        default => 'primary',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('module')
                    ->label('القسم')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('activity_type')
                    ->label('نوع النشاط')
                    ->options([
                        'إضافة' => 'إضافة',
                        'تعديل' => 'تعديل',
                        'حذف' => 'حذف',
                        'تسجيل دخول' => 'تسجيل دخول',
                        'تسجيل خروج' => 'تسجيل خروج',
                    ]),
                    
                SelectFilter::make('module')
                    ->label('القسم')
                    ->options(function () {
                        return ActivityLog::distinct()->pluck('module', 'module')->toArray();
                    }),
                    
                Filter::make('created_at')
                    ->label('تاريخ النشاط')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                // لا نحتاج إلى إجراءات جماعية
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // لا توجد علاقات
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
    
    // منع إنشاء سجلات نشاط جديدة
    public static function canCreate(): bool
    {
        return false;
    }
    
    // منع تعديل سجلات النشاط
    public static function canEdit(Model $record): bool
    {
        return false;
    }
    
    // منع حذف سجلات النشاط
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
