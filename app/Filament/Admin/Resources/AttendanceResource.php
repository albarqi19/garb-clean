<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AttendanceResource\Pages;
use App\Filament\Admin\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    // تعيين أيقونة مناسبة للحضور
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'حضور';
    protected static ?string $pluralModelLabel = 'سجلات الحضور';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم بيانات الحضور
                Forms\Components\Section::make('بيانات الحضور')
                    ->schema([
                        Forms\Components\Select::make('attendable_type')
                            ->label('نوع الشخص')
                            ->options([
                                Teacher::class => 'معلم',
                                Employee::class => 'موظف'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('attendable_id', null)),
                        Forms\Components\Select::make('attendable_id')
                            ->label('اسم الشخص')
                            ->options(function ($get) {
                                $type = $get('attendable_type');
                                if (!$type) return [];
                                
                                if ($type === Teacher::class) {
                                    return Teacher::all()->pluck('name', 'id');
                                } else {
                                    return Employee::all()->pluck('name', 'id');
                                }
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn ($get) => !$get('attendable_type')),
                        Forms\Components\DatePicker::make('date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('period')
                            ->label('الفترة')
                            ->options([
                                'الفجر' => 'الفجر',
                                'العصر' => 'العصر',
                                'المغرب' => 'المغرب',
                                'العشاء' => 'العشاء',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'حاضر' => 'حاضر',
                                'غائب' => 'غائب',
                                'متأخر' => 'متأخر',
                                'مأذون' => 'مأذون',
                            ])
                            ->default('حاضر')
                            ->required(),
                    ])
                    ->columns(2),
                
                // قسم أوقات الحضور والانصراف
                Forms\Components\Section::make('أوقات الحضور والانصراف')
                    ->schema([
                        Forms\Components\DateTimePicker::make('check_in')
                            ->label('وقت الحضور')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('check_out')
                            ->label('وقت الانصراف')
                            ->seconds(false)
                            ->after('check_in'),
                    ])
                    ->columns(2),
                
                // قسم الملاحظات
                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('الفترة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('attendable_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Teacher::class => 'معلم',
                        Employee::class => 'موظف',
                        default => 'غير محدد',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendable_id')
                    ->label('اسم الشخص')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->attendable) {
                            return $record->attendable->name;
                        }
                        return 'غير موجود';
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'حاضر' => 'success',
                        'متأخر' => 'warning',
                        'غائب' => 'danger',
                        'مأذون' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('وقت الحضور')
                    ->dateTime()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('وقت الانصراف')
                    ->dateTime()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('isEligibleForPayment')
                    ->label('مستحق للدفع')
                    ->boolean()
                    ->alignCenter()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'من تاريخ: ' . $data['date_from'];
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators['date_until'] = 'إلى تاريخ: ' . $data['date_until'];
                        }
                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('period')
                    ->label('الفترة')
                    ->options([
                        'الفجر' => 'الفجر',
                        'العصر' => 'العصر',
                        'المغرب' => 'المغرب',
                        'العشاء' => 'العشاء',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'حاضر' => 'حاضر',
                        'غائب' => 'غائب',
                        'متأخر' => 'متأخر',
                        'مأذون' => 'مأذون',
                    ]),
                Tables\Filters\SelectFilter::make('attendable_type')
                    ->label('نوع الشخص')
                    ->options([
                        Teacher::class => 'معلم',
                        Employee::class => 'موظف',
                    ]),
                Tables\Filters\Filter::make('is_eligible_for_payment')
                    ->label('مستحق للدفع')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['حاضر', 'متأخر'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('تحديث الحالة')
                        ->form([
                            Forms\Components\Select::make('new_status')
                                ->label('الحالة الجديدة')
                                ->options([
                                    'حاضر' => 'حاضر',
                                    'غائب' => 'غائب',
                                    'متأخر' => 'متأخر',
                                    'مأذون' => 'مأذون',
                                ])
                                ->required(),
                        ])
                        ->action(function (Builder $query, array $data) {
                            $query->update(['status' => $data['new_status']]);
                        })
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
