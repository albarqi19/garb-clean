<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SalaryRateResource\Pages;
use App\Filament\Admin\Resources\SalaryRateResource\RelationManagers;
use App\Models\SalaryRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryRateResource extends Resource
{
    protected static ?string $model = SalaryRate::class;

    // تعيين أيقونة مناسبة لمعدلات الرواتب
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'معدل الراتب';
    protected static ?string $pluralModelLabel = 'معدلات الرواتب';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->required()
                            ->options([
                                'معلم بمكافأة' => 'معلم بمكافأة',
                                'معلم محتسب' => 'معلم محتسب',
                                'مشرف' => 'مشرف',
                                'مساعد مشرف' => 'مساعد مشرف',
                                'موظف إداري' => 'موظف إداري',
                            ]),
                        Forms\Components\Select::make('nationality_type')
                            ->label('نوع الجنسية')
                            ->required()
                            ->options([
                                'سعودي' => 'سعودي',
                                'غير سعودي' => 'غير سعودي',
                            ]),
                        Forms\Components\DatePicker::make('effective_from')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('effective_to')
                            ->label('تاريخ الانتهاء')
                            ->after('effective_from'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(2),
                
                // قسم معدلات الفترات الرئيسية
                Forms\Components\Section::make('معدلات الفترات الرئيسية (العصر، العشاء)')
                    ->schema([
                        Forms\Components\TextInput::make('main_periods_daily_rate')
                            ->label('المعدل اليومي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\TextInput::make('main_periods_monthly_rate')
                            ->label('المعدل الشهري')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                    ])
                    ->columns(2),
                
                // قسم معدلات فترة المغرب
                Forms\Components\Section::make('معدلات فترة المغرب')
                    ->schema([
                        Forms\Components\TextInput::make('maghrib_daily_rate')
                            ->label('المعدل اليومي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\TextInput::make('maghrib_monthly_rate')
                            ->label('المعدل الشهري')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
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
                Tables\Columns\TextColumn::make('job_title')
                    ->label('المسمى الوظيفي')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality_type')
                    ->label('نوع الجنسية')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('main_periods_daily_rate')
                    ->label('المعدل اليومي للفترات الرئيسية')
                    ->numeric()
                    ->sortable()
                    ->money('SAR'),
                Tables\Columns\TextColumn::make('maghrib_daily_rate')
                    ->label('المعدل اليومي للمغرب')
                    ->numeric()
                    ->sortable()
                    ->money('SAR'),
                Tables\Columns\TextColumn::make('effective_from')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_to')
                    ->label('تاريخ الانتهاء')
                    ->date('Y-m-d')
                    ->placeholder('مستمر')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
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
                Tables\Filters\SelectFilter::make('job_title')
                    ->label('المسمى الوظيفي')
                    ->options([
                        'معلم بمكافأة' => 'معلم بمكافأة',
                        'معلم محتسب' => 'معلم محتسب',
                        'مشرف' => 'مشرف',
                        'مساعد مشرف' => 'مساعد مشرف',
                        'موظف إداري' => 'موظف إداري',
                    ]),
                Tables\Filters\SelectFilter::make('nationality_type')
                    ->label('نوع الجنسية')
                    ->options([
                        'سعودي' => 'سعودي',
                        'غير سعودي' => 'غير سعودي',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الحالات')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (SalaryRate $record) {
                        $new = $record->replicate();
                        $new->effective_from = now();
                        $new->effective_to = null;
                        $new->is_active = true;
                        $new->save();
                        
                        return redirect(self::getUrl('edit', ['record' => $new]));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activateBulk')
                        ->label('تنشيط')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivateBulk')
                        ->label('تعطيل')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
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
            'index' => Pages\ListSalaryRates::route('/'),
            'create' => Pages\CreateSalaryRate::route('/create'),
            'edit' => Pages\EditSalaryRate::route('/{record}/edit'),
        ];
    }
}
