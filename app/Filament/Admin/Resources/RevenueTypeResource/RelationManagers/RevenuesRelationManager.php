<?php

namespace App\Filament\Admin\Resources\RevenueTypeResource\RelationManagers;

use App\Models\AcademicTerm;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RevenuesRelationManager extends RelationManager
{
    protected static string $relationship = 'revenues';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'الإيرادات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->options(QuranCircle::where('circle_status', 'نشطة')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\TextInput::make('month')
                    ->label('الشهر')
                    ->required(),
                    
                Forms\Components\DatePicker::make('revenue_date')
                    ->label('تاريخ الإيراد')
                    ->required()
                    ->default(now()),
                    
                Forms\Components\Select::make('academic_term_id')
                    ->label('الفصل الدراسي')
                    ->options(AcademicTerm::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Forms\Components\TextInput::make('transaction_reference')
                    ->label('مرجع المعاملة')
                    ->nullable(),
                    
                Forms\Components\TextInput::make('donor_name')
                    ->label('اسم المتبرع')
                    ->nullable(),
                    
                Forms\Components\TextInput::make('donor_contact')
                    ->label('معلومات الاتصال بالمتبرع')
                    ->nullable(),
                    
                Forms\Components\Toggle::make('is_for_center')
                    ->label('للمركز؟')
                    ->default(false)
                    ->required(),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('الحلقة القرآنية')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->sortable()
                    ->money('SAR'),
                    
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('revenue_date')
                    ->label('تاريخ الإيراد')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('الفصل الدراسي')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('donor_name')
                    ->label('اسم المتبرع')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_for_center')
                    ->label('للمركز؟')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                // يمكن إضافة عوامل تصفية هنا
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة إيراد جديد')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_by'] = Auth::id();
                        return $data;
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
            ]);
    }
}