<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmployeeResource\Pages;
use App\Filament\Admin\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    // تعيين أيقونة مناسبة للموظفين
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'موظف';
    protected static ?string $pluralModelLabel = 'الموظفين';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'الإدارية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الشخصية
                Forms\Components\Section::make('البيانات الشخصية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('identity_number')
                            ->label('رقم الهوية')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('تاريخ التعيين'),
                    ])
                    ->columns(2),
                
                // قسم بيانات الوظيفة
                Forms\Components\Section::make('بيانات الوظيفة')
                    ->schema([
                        Forms\Components\TextInput::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cost_center')
                            ->label('مركز التكلفة')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('association_employee_number')
                            ->label('الرقم الوظيفي بالجمعية')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم فترات العمل
                Forms\Components\Section::make('فترات العمل')
                    ->schema([
                        Forms\Components\Toggle::make('afternoon_shift')
                            ->label('فترة العصر')
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\Toggle::make('maghrib_shift')
                            ->label('فترة المغرب')
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\Toggle::make('isha_shift')
                            ->label('فترة العشاء')
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(3),
                
                // قسم المستندات والروابط
                Forms\Components\Section::make('المستندات والروابط')
                    ->schema([
                        Forms\Components\TextInput::make('appointment_decision_link')
                            ->label('رابط قرار التعيين')
                            ->url()
                            ->maxLength(255)
                            ->suffixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('amendment_form_link')
                            ->label('رابط نموذج التعديل')
                            ->url()
                            ->maxLength(255)
                            ->suffixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('circle_permit_link')
                            ->label('رابط تصريح الحلقة')
                            ->url()
                            ->maxLength(255)
                            ->suffixIcon('heroicon-o-link'),
                    ])
                    ->columns(3),
                
                // قسم الملاحظات
                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('branch_notes')
                            ->label('ملاحظات الفرع')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('hr_notes')
                            ->label('ملاحظات الموارد البشرية')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('data_entry_notes')
                            ->label('ملاحظات إدخال البيانات')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('المسمى الوظيفي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('identity_number')
                    ->label('رقم الهوية')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workShifts')
                    ->label('فترات العمل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->label('تاريخ التعيين')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('hire_date')
                    ->form([
                        Forms\Components\DatePicker::make('hire_from')
                            ->label('تاريخ التعيين من'),
                        Forms\Components\DatePicker::make('hire_until')
                            ->label('تاريخ التعيين إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['hire_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '>=', $date),
                            )
                            ->when(
                                $data['hire_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['hire_from'] ?? null) {
                            $indicators['hire_from'] = 'تاريخ التعيين من ' . $data['hire_from'];
                        }
                        if ($data['hire_until'] ?? null) {
                            $indicators['hire_until'] = 'تاريخ التعيين إلى ' . $data['hire_until'];
                        }
                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('job_title')
                    ->label('المسمى الوظيفي')
                    ->options(fn(): array => Employee::distinct()->pluck('job_title', 'job_title')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
