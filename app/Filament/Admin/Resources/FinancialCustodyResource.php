<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FinancialCustodyResource\Pages;
use App\Filament\Admin\Resources\FinancialCustodyResource\RelationManagers;
use App\Models\FinancialCustody;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FinancialCustodyResource extends Resource
{
    protected static ?string $model = FinancialCustody::class;

    // تعيين أيقونة مناسبة للعهد المالية
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'عهدة مالية';
    protected static ?string $pluralModelLabel = 'العهد المالية';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->label('رقم الطلب')
                            ->default(fn () => FinancialCustody::generateRequestNumber())
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('requester_id')
                            ->label('مقدم الطلب')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('requester_job_title')
                            ->label('المسمى الوظيفي لمقدم الطلب')
                            ->maxLength(255),
                        Forms\Components\Select::make('mosque_id')
                            ->label('المسجد')
                            ->relationship('mosque', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                
                // قسم البيانات المالية
                Forms\Components\Section::make('البيانات المالية')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'جديد' => 'جديد',
                                'قيد المراجعة' => 'قيد المراجعة',
                                'معتمد' => 'معتمد',
                                'مرفوض' => 'مرفوض',
                                'تم الصرف' => 'تم الصرف',
                                'مغلق' => 'مغلق',
                            ])
                            ->default('جديد')
                            ->required(),
                        Forms\Components\Select::make('disbursement_method')
                            ->label('طريقة الصرف')
                            ->options([
                                'حضوري' => 'حضوري',
                                'تحويل بنكي' => 'تحويل بنكي',
                            ])
                            ->placeholder('يتم تحديدها تلقائيًا'),
                    ])
                    ->columns(3),
                
                // قسم بيانات التواريخ
                Forms\Components\Section::make('بيانات التواريخ')
                    ->schema([
                        Forms\Components\DatePicker::make('request_date')
                            ->label('تاريخ الطلب')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('approval_date')
                            ->label('تاريخ الاعتماد')
                            ->after('request_date'),
                        Forms\Components\DatePicker::make('disbursement_date')
                            ->label('تاريخ الصرف')
                            ->after('request_date'),
                    ])
                    ->columns(3),
                
                // قسم المعلومات الإضافية
                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Select::make('approved_by')
                            ->label('تم الاعتماد بواسطة')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('مقدم الطلب')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester_job_title')
                    ->label('المسمى الوظيفي')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mosque.name')
                    ->label('المسجد')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_amount')
                    ->label('المستخدم')
                    ->money('SAR')
                    ->getStateUsing(fn (FinancialCustody $record): float => $record->used_amount ?? 0)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('SAR')
                    ->getStateUsing(fn (FinancialCustody $record): float => $record->remaining_amount ?? 0)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'جديد' => 'info',
                        'قيد المراجعة' => 'warning',
                        'معتمد' => 'success',
                        'مرفوض' => 'danger',
                        'تم الصرف' => 'primary',
                        'مغلق' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('request_date')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('requester_id')
                    ->label('مقدم الطلب')
                    ->relationship('requester', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('mosque_id')
                    ->label('المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'جديد' => 'جديد',
                        'قيد المراجعة' => 'قيد المراجعة',
                        'معتمد' => 'معتمد',
                        'مرفوض' => 'مرفوض',
                        'تم الصرف' => 'تم الصرف',
                        'مغلق' => 'مغلق',
                    ]),
                Tables\Filters\Filter::make('request_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (FinancialCustody $record) => in_array($record->status, ['جديد', 'قيد المراجعة']))
                    ->requiresConfirmation()
                    ->action(function (FinancialCustody $record) {
                        $record->updateStatus('معتمد', Auth::id());
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FinancialCustody $record) => in_array($record->status, ['جديد', 'قيد المراجعة']))
                    ->requiresConfirmation()
                    ->action(function (FinancialCustody $record) {
                        $record->updateStatus('مرفوض');
                    }),
                Tables\Actions\Action::make('disburse')
                    ->label('صرف')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn (FinancialCustody $record) => $record->status === 'معتمد')
                    ->requiresConfirmation()
                    ->action(function (FinancialCustody $record) {
                        $record->updateStatus('تم الصرف');
                    }),
                Tables\Actions\Action::make('close')
                    ->label('إغلاق')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (FinancialCustody $record) => $record->status === 'تم الصرف')
                    ->requiresConfirmation()
                    ->action(function (FinancialCustody $record) {
                        $record->updateStatus('مغلق');
                    }),
                Tables\Actions\Action::make('addReceipt')
                    ->label('إضافة إيصال')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn (FinancialCustody $record) => in_array($record->status, ['تم الصرف', 'معتمد']))
                    ->url(fn (FinancialCustody $record): string => route('filament.admin.resources.custody-receipts.create', ['custodyId' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatusBulk')
                        ->label('تغيير الحالة للمحدد')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('الحالة الجديدة')
                                ->options([
                                    'جديد' => 'جديد',
                                    'قيد المراجعة' => 'قيد المراجعة',
                                    'معتمد' => 'معتمد',
                                    'مرفوض' => 'مرفوض',
                                    'تم الصرف' => 'تم الصرف',
                                    'مغلق' => 'مغلق',
                                ])
                                ->required(),
                        ])
                        ->action(function (Builder $query, array $data) {
                            $query->update(['status' => $data['status']]);
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
            'index' => Pages\ListFinancialCustodies::route('/'),
            'create' => Pages\CreateFinancialCustody::route('/create'),
            'edit' => Pages\EditFinancialCustody::route('/{record}/edit'),
        ];
    }
}
