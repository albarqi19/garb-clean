<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherTransferRequestResource\Pages;
use App\Filament\Admin\Resources\TeacherTransferRequestResource\RelationManagers;
use App\Models\TeacherTransferRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class TeacherTransferRequestResource extends Resource
{
    protected static ?string $model = TeacherTransferRequest::class;

    // تخصيص المورد بالعربية
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $label = 'طلب نقل معلم';
    protected static ?string $pluralLabel = 'طلبات نقل المعلمين';
    protected static ?string $navigationGroup = 'طلبات الخدمة';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('بيانات المعلم')
                    ->description('معلومات المعلم المراد نقله')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('request_date')
                            ->label('تاريخ الطلب')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('preferred_time')
                            ->label('الوقت المفضل'),
                    ])->columns(3),

                Section::make('معلومات النقل')
                    ->schema([
                        Forms\Components\Select::make('current_circle_id')
                            ->label('الحلقة الحالية')
                            ->relationship('currentCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('requested_circle_id')
                            ->label('الحلقة المطلوبة')
                            ->relationship('requestedCircle', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('current_mosque_id')
                            ->label('المسجد الحالي')
                            ->relationship('currentMosque', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('requested_mosque_id')
                            ->label('المسجد المطلوب')
                            ->relationship('requestedMosque', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('requested_neighborhood')
                            ->label('الحي المطلوب')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('أسباب وملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('transfer_reason')
                            ->label('سبب النقل')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('حالة الطلب')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'in_progress' => 'قيد المعالجة',
                                'approved' => 'تمت الموافقة',
                                'rejected' => 'مرفوض',
                                'completed' => 'مكتمل',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('response_date')
                            ->label('تاريخ الرد'),
                        Forms\Components\Textarea::make('response_notes')
                            ->label('ملاحظات الرد')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('approved_by')
                            ->label('تمت الموافقة بواسطة')
                            ->numeric(),
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('تاريخ النقل'),
                    ])->columns(2),

                Section::make('قرار التعيين')
                    ->schema([
                        Forms\Components\Toggle::make('has_appointment_decision')
                            ->label('لديه قرار تعيين')
                            ->required(),
                        Forms\Components\TextInput::make('appointment_decision_number')
                            ->label('رقم قرار التعيين')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('has_appointment_decision')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currentCircle.name')
                    ->label('الحلقة الحالية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requestedCircle.name')
                    ->label('الحلقة المطلوبة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_neighborhood')
                    ->label('الحي المطلوب')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->label('تاريخ الطلب')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('response_date')
                    ->label('تاريخ الرد')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('تاريخ النقل')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_appointment_decision')
                    ->label('قرار التعيين')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد المعالجة',
                        'approved' => 'تمت الموافقة',
                        'rejected' => 'مرفوض',
                        'completed' => 'مكتمل',
                    ]),
                SelectFilter::make('has_appointment_decision')
                    ->label('قرار التعيين')
                    ->options([
                        '1' => 'موجود',
                        '0' => 'غير موجود',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('الموافقة')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (TeacherTransferRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'response_date' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                    })
                    ->visible(fn (TeacherTransferRequest $record) => $record->status == 'pending' || $record->status == 'in_progress'),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->form([
                        Forms\Components\Textarea::make('response_notes')
                            ->label('سبب الرفض')
                            ->required(),
                    ])
                    ->action(function (TeacherTransferRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'response_date' => now(),
                            'response_notes' => $data['response_notes'],
                        ]);
                    })
                    ->visible(fn (TeacherTransferRequest $record) => $record->status == 'pending' || $record->status == 'in_progress'),
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->form([
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('تاريخ النقل')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (TeacherTransferRequest $record, array $data) {
                        $record->update([
                            'status' => 'completed',
                            'transfer_date' => $data['transfer_date'],
                        ]);
                    })
                    ->visible(fn (TeacherTransferRequest $record) => $record->status == 'approved'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('الموافقة على المحدد')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update([
                            'status' => 'approved',
                            'response_date' => now(),
                            'approved_by' => auth()->id(),
                        ])))
                        ->requiresConfirmation()
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
            'index' => Pages\ListTeacherTransferRequests::route('/'),
            'create' => Pages\CreateTeacherTransferRequest::route('/create'),
            'edit' => Pages\EditTeacherTransferRequest::route('/{record}/edit'),
        ];
    }
}
