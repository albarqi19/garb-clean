<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SalaryResource\Pages;
use App\Filament\Admin\Resources\SalaryResource\RelationManagers;
use App\Models\Salary;
use App\Models\Teacher;
use App\Models\Employee;
use App\Models\AcademicTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    // تعيين أيقونة مناسبة للرواتب
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'راتب';
    protected static ?string $pluralModelLabel = 'الرواتب';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('payee_type')
                            ->label('نوع المستلم')
                            ->options([
                                Teacher::class => 'معلم',
                                Employee::class => 'موظف'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('payee_id', null)),
                        Forms\Components\Select::make('payee_id')
                            ->label('اسم المستلم')
                            ->options(function ($get) {
                                $type = $get('payee_type');
                                if (!$type) return [];
                                
                                if ($type === Teacher::class) {
                                    return Teacher::all()->pluck('name', 'id');
                                } else {
                                    return Employee::all()->pluck('name', 'id');
                                }
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn ($get) => !$get('payee_type'))
                            ->live(),
                        Forms\Components\Select::make('academic_term_id')
                            ->label('الفصل الدراسي')
                            ->relationship('academicTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('month')
                            ->label('الشهر')
                            ->placeholder('مثال: ديسمبر 2023')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم بيانات المبالغ
                Forms\Components\Section::make('بيانات المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('base_amount')
                            ->label('المبلغ الأساسي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\TextInput::make('attendance_days')
                            ->label('عدد أيام الحضور')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('deductions')
                            ->label('الخصومات')
                            ->numeric()
                            ->default(0)
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\TextInput::make('bonuses')
                            ->label('المكافآت')
                            ->numeric()
                            ->default(0)
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                    ])
                    ->columns(2),
                
                // قسم بيانات الدفع
                Forms\Components\Section::make('بيانات الدفع')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->label('تم الدفع')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->live(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->visible(fn ($get) => $get('is_paid')),
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('المرجع')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_paid')),
                        Forms\Components\TextInput::make('iban')
                            ->label('رقم الآيبان')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('ملاحظات الدفع')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payee_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Teacher::class => 'معلم',
                        Employee::class => 'موظف',
                        default => 'غير محدد',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payee_id')
                    ->label('اسم المستلم')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->payee) {
                            return $record->payee->name ?? 'غير موجود';
                        }
                        return 'غير موجود';
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('الفصل الدراسي')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_amount')
                    ->label('المبلغ الأساسي')
                    ->numeric()
                    ->sortable()
                    ->money('SAR'),
                Tables\Columns\TextColumn::make('attendance_days')
                    ->label('أيام الحضور')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('deductions')
                    ->label('الخصومات')
                    ->numeric()
                    ->sortable()
                    ->money('SAR')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('bonuses')
                    ->label('المكافآت')
                    ->numeric()
                    ->sortable()
                    ->money('SAR')
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric()
                    ->sortable()
                    ->money('SAR')
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('تم الدفع')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->date()
                    ->sortable()
                    ->placeholder('لم يتم الدفع بعد'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payee_type')
                    ->label('نوع المستلم')
                    ->options([
                        Teacher::class => 'معلم',
                        Employee::class => 'موظف',
                    ]),
                Tables\Filters\SelectFilter::make('academic_term_id')
                    ->label('الفصل الدراسي')
                    ->relationship('academicTerm', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('حالة الدفع')
                    ->placeholder('جميع الحالات')
                    ->trueLabel('تم الدفع')
                    ->falseLabel('قيد الانتظار'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('تعليم كمدفوع')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Salary $record) => !$record->is_paid)
                    ->action(function (Salary $record) {
                        $record->update([
                            'is_paid' => true,
                            'payment_date' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsPaidBulk')
                        ->label('تعليم كمدفوع')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Builder $query) => $query->update([
                            'is_paid' => true,
                            'payment_date' => now(),
                        ]))
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
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }

    // دالة للحصول على الاستعلام الأولي
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
