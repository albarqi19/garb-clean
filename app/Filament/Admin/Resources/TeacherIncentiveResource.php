<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherIncentiveResource\Pages;
use App\Filament\Admin\Resources\TeacherIncentiveResource\RelationManagers;
use App\Models\TeacherIncentive;
use App\Models\CircleIncentive;
use App\Models\Teacher;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TeacherIncentiveResource extends Resource
{
    protected static ?string $model = TeacherIncentive::class;

    // تعيين أيقونة مناسبة لحوافز المعلمين
    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'حافز معلم';
    protected static ?string $pluralModelLabel = 'حوافز المعلمين';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('circle_incentive_id')
                            ->label('حافز الحلقة')
                            ->relationship('circleIncentive', 'sponsor_name')
                            ->getOptionLabelFromRecordUsing(fn (CircleIncentive $record) => "{$record->quranCircle->name} - {$record->sponsor_name} ({$record->remaining_amount} ر.س)")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->get('circleIncentiveId'))
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, $state, $get) => $set('amount', 0))
                            ->disabled(fn () => request()->has('circleIncentiveId'))
                            ->helperText(
                                fn (?CircleIncentive $record) => $record
                                    ? "المبلغ المتبقي: {$record->remaining_amount} ر.س"
                                    : null
                            ),
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الحافز')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(function (Forms\Get $get) {
                                $circleIncentiveId = $get('circle_incentive_id');
                                if (!$circleIncentiveId) return 0;
                                
                                $circleIncentive = CircleIncentive::find($circleIncentiveId);
                                
                                if (!$circleIncentive) return 0;
                                
                                return $circleIncentive->remaining_amount;
                            })
                            ->helperText('لا يمكن أن يتجاوز المبلغ المتاح في حافز الحلقة'),
                    ])
                    ->columns(2),
                
                // قسم بيانات الاعتماد
                Forms\Components\Section::make('بيانات الاعتماد')
                    ->schema([
                        Forms\Components\TextInput::make('reason')
                            ->label('سبب الحافز')
                            ->placeholder('مثال: التميز في التدريس، التزامه بمواعيد الحصص')
                            ->maxLength(255),
                        Forms\Components\Select::make('approved_by')
                            ->label('المعتمد')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->default(Auth::id()),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('circleIncentive.quranCircle.name')
                    ->label('الحلقة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(30)
                    ->tooltip(fn (TeacherIncentive $record): ?string => $record->reason)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('المعتمد')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('circleIncentive.academic_term_id')
                    ->label('الفصل الدراسي')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'غير محدد';
                        $academicTerm = \App\Models\AcademicTerm::find($state);
                        return $academicTerm ? $academicTerm->name : 'غير محدد';
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('circle_incentive_id')
                    ->label('حافز الحلقة')
                    ->relationship('circleIncentive.quranCircle', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('approved_by')
                    ->label('المعتمد')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('إلغاء الحافز')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم إلغاء هذا الحافز وإرجاع المبلغ إلى حافز الحلقة. هل أنت متأكد؟')
                    ->action(fn (TeacherIncentive $record) => $record->cancel()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(
                null
            );
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
            'index' => Pages\ListTeacherIncentives::route('/'),
            'create' => Pages\CreateTeacherIncentive::route('/create'),
            'edit' => Pages\EditTeacherIncentive::route('/{record}/edit'),
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
