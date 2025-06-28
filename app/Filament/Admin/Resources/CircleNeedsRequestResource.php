<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleNeedsRequestResource\Pages;
use App\Filament\Admin\Resources\CircleNeedsRequestResource\RelationManagers;
use App\Models\CircleNeedsRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class CircleNeedsRequestResource extends Resource
{
    protected static ?string $model = CircleNeedsRequest::class;

    // تخصيص المورد بالعربية
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $label = 'طلب احتياج حلقة';
    protected static ?string $pluralLabel = 'طلبات احتياج الحلقات';
    protected static ?string $navigationGroup = 'طلبات الخدمة';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحلقة')
                    ->description('معلومات أساسية عن الحلقة وموقعها')
                    ->schema([
                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة')
                            ->relationship('quranCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('school_name')
                            ->label('اسم المدرسة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('time_period')
                            ->label('الفترة الزمنية')
                            ->required(),
                        Forms\Components\TextInput::make('neighborhood')
                            ->label('الحي')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                
                Section::make('الاحتياجات المطلوبة')
                    ->schema([
                        Forms\Components\TextInput::make('teachers_needed')
                            ->label('المعلمون المطلوبون')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('supervisors_needed')
                            ->label('المشرفون المطلوبون')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('talqeen_teachers_needed')
                            ->label('معلمو التلقين المطلوبون')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('memorization_teachers_needed')
                            ->label('معلمو التحفيظ المطلوبون')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('assistant_supervisors_needed')
                            ->label('مساعدو المشرفين المطلوبون')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(3),
                
                Section::make('الوضع الحالي')
                    ->schema([
                        Forms\Components\TextInput::make('current_students_count')
                            ->label('عدد الطلاب الحاليين')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('current_teachers_count')
                            ->label('عدد المعلمين الحاليين')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\Select::make('funding_status')
                            ->label('حالة التمويل')
                            ->required()
                            ->options([
                                'funded' => 'ممول',
                                'partially_funded' => 'ممول جزئيًا',
                                'not_funded' => 'غير ممول',
                                'seeking_funding' => 'يبحث عن تمويل'
                            ]),
                        Forms\Components\Select::make('school_status')
                            ->label('حالة المدرسة')
                            ->required()
                            ->options([
                                'operational' => 'تعمل',
                                'under_construction' => 'تحت الإنشاء',
                                'closed' => 'مغلقة',
                                'seasonal' => 'موسمية'
                            ]),
                    ])->columns(2),
                
                Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\Select::make('action')
                            ->label('الإجراء المطلوب')
                            ->required()
                            ->options([
                                'add_teachers' => 'إضافة معلمين',
                                'add_supervisors' => 'إضافة مشرفين',
                                'funding_request' => 'طلب تمويل',
                                'expand_circle' => 'توسعة الحلقة',
                                'other' => 'أخرى'
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('requested_by')
                            ->label('طُلب بواسطة')
                            ->numeric(),
                        Forms\Components\TextInput::make('processed_by')
                            ->label('تمت معالجته بواسطة')
                            ->numeric(),
                        Forms\Components\DatePicker::make('approval_date')
                            ->label('تاريخ الموافقة'),
                        Forms\Components\DatePicker::make('completion_date')
                            ->label('تاريخ الإتمام'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('school_name')
                    ->label('المدرسة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('الحي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('teachers_needed')
                    ->label('المعلمون المطلوبون')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('funding_status')
                    ->label('حالة التمويل')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'funded' => 'success',
                        'partially_funded' => 'warning',
                        'not_funded' => 'danger',
                        'seeking_funding' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('action')
                    ->label('الإجراء المطلوب'),
                Tables\Columns\TextColumn::make('approval_date')
                    ->label('تاريخ الموافقة')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('تاريخ الإتمام')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('funding_status')
                    ->label('حالة التمويل')
                    ->options([
                        'funded' => 'ممول',
                        'partially_funded' => 'ممول جزئيًا',
                        'not_funded' => 'غير ممول',
                        'seeking_funding' => 'يبحث عن تمويل'
                    ]),
                SelectFilter::make('school_status')
                    ->label('حالة المدرسة')
                    ->options([
                        'operational' => 'تعمل',
                        'under_construction' => 'تحت الإنشاء',
                        'closed' => 'مغلقة',
                        'seasonal' => 'موسمية'
                    ]),
                SelectFilter::make('action')
                    ->label('الإجراء المطلوب')
                    ->options([
                        'add_teachers' => 'إضافة معلمين',
                        'add_supervisors' => 'إضافة مشرفين',
                        'funding_request' => 'طلب تمويل',
                        'expand_circle' => 'توسعة الحلقة',
                        'other' => 'أخرى'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (CircleNeedsRequest $record) {
                        $record->update([
                            'approval_date' => now(),
                            'processed_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (CircleNeedsRequest $record) {
                        $record->update([
                            'completion_date' => now(),
                        ]);
                    })
                    ->visible(fn (CircleNeedsRequest $record) => $record->approval_date && !$record->completion_date),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('موافقة على المحدد')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update([
                            'approval_date' => now(),
                            'processed_by' => auth()->id(),
                        ])))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CircleNeedsRequestActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleNeedsRequests::route('/'),
            'create' => Pages\CreateCircleNeedsRequest::route('/create'),
            'edit' => Pages\EditCircleNeedsRequest::route('/{record}/edit'),
        ];
    }
}
