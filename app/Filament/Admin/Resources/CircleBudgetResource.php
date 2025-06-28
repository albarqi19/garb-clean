<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleBudgetResource\Pages;
use App\Filament\Admin\Resources\CircleBudgetResource\RelationManagers;
use App\Models\CircleBudget;
use App\Models\QuranCircle;
use App\Models\AcademicTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CircleBudgetResource extends Resource
{
    protected static ?string $model = CircleBudget::class;

    // تعيين أيقونة مناسبة لميزانيات الحلقات
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'ميزانية حلقة';
    protected static ?string $pluralModelLabel = 'ميزانيات الحلقات';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('quranCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required(),
                                Forms\Components\Select::make('mosque_id')
                                    ->label('المسجد')
                                    ->relationship('mosque', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Forms\Components\Select::make('academic_term_id')
                            ->label('الفصل الدراسي')
                            ->relationship('academicTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('month')
                            ->label('الشهر')
                            ->placeholder('مثال: ديسمبر 2023')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم بيانات الإيرادات
                Forms\Components\Section::make('بيانات الإيرادات')
                    ->schema([
                        Forms\Components\TextInput::make('total_revenue')
                            ->label('إجمالي الإيرادات')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                        Forms\Components\TextInput::make('incentives_amount')
                            ->label('مبلغ الحوافز')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                        Forms\Components\TextInput::make('extra_revenue')
                            ->label('إيرادات إضافية')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                    ])
                    ->columns(3),
                
                // قسم بيانات المصاريف
                Forms\Components\Section::make('بيانات المصاريف')
                    ->schema([
                        Forms\Components\TextInput::make('salary_expenses')
                            ->label('مصاريف الرواتب')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                        Forms\Components\TextInput::make('operational_expenses')
                            ->label('المصاريف التشغيلية')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                        Forms\Components\TextInput::make('other_expenses')
                            ->label('مصاريف أخرى')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live(),
                    ])
                    ->columns(3),
                
                // قسم بيانات الفائض
                Forms\Components\Section::make('بيانات الفائض والعجز')
                    ->schema([
                        Forms\Components\TextInput::make('surplus_amount')
                            ->label('مبلغ الفائض / العجز')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('has_surplus')
                            ->label('يوجد فائض')
                            ->helperText('يتم تحديد هذا القيمة تلقائياً بناءً على المدخلات')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_at_risk')
                            ->label('الميزانية معرضة للخطر')
                            ->helperText('تفعيل هذا الخيار يعني أن الميزانية تحتاج إجراءات عاجلة للحفاظ على توازنها')
                            ->default(false)
                            ->onColor('danger')
                            ->offColor('success'),
                    ])
                    ->columns(3),
                
                // قسم الملاحظات
                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('اسم الحلقة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quranCircle.mosque.name')
                    ->label('المسجد')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('الفصل الدراسي')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('الإيرادات')
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('salary_expenses')
                    ->label('الرواتب')
                    ->money('SAR')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('operational_expenses')
                    ->label('التشغيلية')
                    ->money('SAR')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('surplus_amount')
                    ->label('الفائض / العجز')
                    ->money('SAR')
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('has_surplus')
                    ->label('فائض')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_at_risk')
                    ->label('خطر')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->relationship('quranCircle', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('academic_term_id')
                    ->label('الفصل الدراسي')
                    ->relationship('academicTerm', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('has_surplus')
                    ->label('حالة الميزانية')
                    ->trueLabel('فائض')
                    ->falseLabel('عجز'),
                Tables\Filters\TernaryFilter::make('is_at_risk')
                    ->label('معرضة للخطر')
                    ->trueLabel('نعم')
                    ->falseLabel('لا'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')
                    ->label('إعادة حساب')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (CircleBudget $record) {
                        // حساب إجمالي الإيرادات
                        $totalRevenue = $record->total_revenue + $record->incentives_amount + $record->extra_revenue;
                        
                        // حساب إجمالي المصروفات
                        $totalExpenses = $record->salary_expenses + $record->operational_expenses + $record->other_expenses;
                        
                        // حساب الفائض أو العجز
                        $surplusAmount = $totalRevenue - $totalExpenses;
                        
                        // تحديث البيانات
                        $record->update([
                            'surplus_amount' => $surplusAmount,
                            'has_surplus' => $surplusAmount > 0,
                        ]);
                        
                        // تحديث حالة حوافز الحلقة المرتبطة
                        $circleIncentives = \App\Models\CircleIncentive::where('quran_circle_id', $record->quran_circle_id)
                            ->where('academic_term_id', $record->academic_term_id)
                            ->get();
                            
                        foreach ($circleIncentives as $incentive) {
                            $incentive->updateBlockStatus();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('recalculateBulk')
                        ->label('إعادة حساب المحدد')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Builder $query) {
                            $query->get()->each(function (CircleBudget $record) {
                                // حساب إجمالي الإيرادات
                                $totalRevenue = $record->total_revenue + $record->incentives_amount + $record->extra_revenue;
                                
                                // حساب إجمالي المصروفات
                                $totalExpenses = $record->salary_expenses + $record->operational_expenses + $record->other_expenses;
                                
                                // حساب الفائض أو العجز
                                $surplusAmount = $totalRevenue - $totalExpenses;
                                
                                // تحديث البيانات
                                $record->update([
                                    'surplus_amount' => $surplusAmount,
                                    'has_surplus' => $surplusAmount > 0,
                                ]);
                            });
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
            'index' => Pages\ListCircleBudgets::route('/'),
            'create' => Pages\CreateCircleBudget::route('/create'),
            'edit' => Pages\EditCircleBudget::route('/{record}/edit'),
        ];
    }

    // حدث يتم تنفيذه بعد إنشاء ميزانية جديدة لحلقة
    public static function afterCreate(CircleBudget $budget): void
    {
        $totalRevenue = $budget->total_revenue + $budget->incentives_amount + $budget->extra_revenue;
        $totalExpenses = $budget->salary_expenses + $budget->operational_expenses + $budget->other_expenses;
        $surplusAmount = $totalRevenue - $totalExpenses;
        
        $budget->update([
            'surplus_amount' => $surplusAmount,
            'has_surplus' => $surplusAmount > 0,
        ]);
    }
    
    // حدث يتم تنفيذه بعد تحديث ميزانية حلقة
    public static function afterSave(CircleBudget $budget): void
    {
        $totalRevenue = $budget->total_revenue + $budget->incentives_amount + $budget->extra_revenue;
        $totalExpenses = $budget->salary_expenses + $budget->operational_expenses + $budget->other_expenses;
        $surplusAmount = $totalRevenue - $totalExpenses;
        
        if ($budget->surplus_amount != $surplusAmount || $budget->has_surplus != ($surplusAmount > 0)) {
            $budget->update([
                'surplus_amount' => $surplusAmount,
                'has_surplus' => $surplusAmount > 0,
            ]);
            
            // تحديث حالة حوافز الحلقة المرتبطة
            $circleIncentives = \App\Models\CircleIncentive::where('quran_circle_id', $budget->quran_circle_id)
                ->where('academic_term_id', $budget->academic_term_id)
                ->get();
                
            foreach ($circleIncentives as $incentive) {
                $incentive->updateBlockStatus();
            }
        }
    }
}
