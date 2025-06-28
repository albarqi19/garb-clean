<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RevenueResource\Pages;
use App\Filament\Admin\Resources\RevenueResource\RelationManagers;
use App\Models\Revenue;
use App\Models\QuranCircle;
use App\Models\RevenueType;
use App\Models\AcademicTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RevenueResource extends Resource
{
    protected static ?string $model = Revenue::class;

    // تعيين الأيقونة إلى أيقونة الإيرادات
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    // الترجمة العربية للمورد
    protected static ?string $modelLabel = 'إيراد';
    protected static ?string $pluralModelLabel = 'الإيرادات';
    
    // تعيين مجموعة التنقل إلى "المالية"
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب الظهور في القائمة
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الإيراد الأساسية')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('revenue_type_id')
                        ->label('نوع الإيراد')
                        ->options(RevenueType::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                        
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ (ريال)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01),
                        
                    Forms\Components\Select::make('quran_circle_id')
                        ->label('الحلقة القرآنية')
                        ->options(QuranCircle::where('circle_status', 'نشطة')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable(),
                        
                    Forms\Components\Toggle::make('is_for_center')
                        ->label('الإيراد للمركز')
                        ->helperText('في حال كان الإيراد للمركز وليس لحلقة محددة')
                        ->default(false)
                        ->required(),

                    Forms\Components\DatePicker::make('revenue_date')
                        ->label('تاريخ الإيراد')
                        ->required()
                        ->default(now()),
                        
                    Forms\Components\TextInput::make('month')
                        ->label('الشهر')
                        ->placeholder('مثال: 2025-04')
                        ->required()
                        ->helperText('صيغة الشهر هي YYYY-MM')
                        ->maxLength(7),
                        
                    Forms\Components\Select::make('academic_term_id')
                        ->label('الفصل الدراسي')
                        ->options(AcademicTerm::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
                
                Forms\Components\Section::make('معلومات المتبرع')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('donor_name')
                        ->label('اسم المتبرع')
                        ->maxLength(255),
                        
                    Forms\Components\TextInput::make('donor_contact')
                        ->label('معلومات الاتصال بالمتبرع')
                        ->maxLength(255),
                        
                    Forms\Components\TextInput::make('transaction_reference')
                        ->label('مرجع المعاملة')
                        ->helperText('مثال: رقم الشيك، رقم الحوالة')
                        ->maxLength(255),
                ]),
                
                Forms\Components\Section::make('ملاحظات إضافية')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->placeholder('أي ملاحظات إضافية حول الإيراد')
                        ->columnSpanFull(),
                ]),
                
                Forms\Components\Hidden::make('recorded_by')
                    ->default(Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('revenueType.name')
                    ->label('نوع الإيراد')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_for_center')
                    ->label('للمركز؟')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('revenue_date')
                    ->label('تاريخ الإيراد')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('الفصل الدراسي')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('donor_name')
                    ->label('المتبرع')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('recorder.name')
                    ->label('سجل بواسطة')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('revenue_type_id')
                    ->label('نوع الإيراد')
                    ->options(RevenueType::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->options(QuranCircle::where('circle_status', 'نشطة')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('academic_term_id')
                    ->label('الفصل الدراسي')
                    ->options(AcademicTerm::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_for_center')
                    ->label('للمركز؟')
                    ->placeholder('الكل')
                    ->trueLabel('نعم')
                    ->falseLabel('لا'),
                    
                Tables\Filters\Filter::make('month')
                    ->label('الشهر')
                    ->form([
                        Forms\Components\TextInput::make('month')
                            ->label('الشهر')
                            ->placeholder('مثال: 2025-04'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['month'],
                            fn (Builder $query, $month): Builder => $query->where('month', 'like', '%' . $month . '%')
                        );
                    }),
                    
                Tables\Filters\Filter::make('revenue_date')
                    ->label('تاريخ الإيراد')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('revenue_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('revenue_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
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
            ])
            ->defaultSort('revenue_date', 'desc');
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
            'index' => Pages\ListRevenues::route('/'),
            'create' => Pages\CreateRevenue::route('/create'),
            'edit' => Pages\EditRevenue::route('/{record}/edit'),
        ];
    }
}
