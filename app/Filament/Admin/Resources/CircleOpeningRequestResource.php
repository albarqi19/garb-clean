<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleOpeningRequestResource\Pages;
use App\Filament\Admin\Resources\CircleOpeningRequestResource\RelationManagers;
use App\Models\CircleOpeningRequest;
use App\Models\Mosque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\MaxWidth;

class CircleOpeningRequestResource extends Resource
{
    protected static ?string $model = CircleOpeningRequest::class;

    // تخصيص المورد بالعربية
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $label = 'طلب فتح حلقة';
    protected static ?string $pluralLabel = 'طلبات فتح الحلقات';    protected static ?string $navigationGroup = 'إدارة المساجد والحلقات';
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('بيانات مقدم الطلب')
                    ->description('معلومات الشخص المتقدم بطلب فتح حلقة')
                    ->schema([
                        Forms\Components\TextInput::make('requester_name')
                            ->label('اسم مقدم الطلب')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('requester_phone')
                            ->label('رقم جوال مقدم الطلب')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('requester_relation_to_circle')
                            ->label('علاقة مقدم الطلب بالحلقة')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                Section::make('معلومات المسجد والمنطقة')
                    ->schema([
                        Forms\Components\TextInput::make('neighborhood')
                            ->label('الحي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('mosque_id')
                            ->label('المسجد')
                            ->searchable()
                            ->preload()
                            ->relationship('mosque', 'name'),
                        Forms\Components\TextInput::make('mosque_name')
                            ->label('اسم المسجد')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mosque_location_url')
                            ->label('رابط موقع المسجد')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nearest_circle')
                            ->label('أقرب حلقة')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('تفاصيل الحلقة المطلوبة')
                    ->schema([
                        Forms\Components\TextInput::make('number_of_circles_requested')
                            ->label('عدد الحلقات المطلوبة')
                            ->required()
                            ->numeric(),
                        Forms\Components\Toggle::make('had_previous_circles')
                            ->label('هل سبق وكان هناك حلقات؟')
                            ->required(),
                        Forms\Components\TextInput::make('expected_students_number')
                            ->label('العدد المتوقع للطلاب')
                            ->numeric(),
                        Forms\Components\Toggle::make('is_mosque_owner_welcoming')
                            ->label('هل إمام المسجد مرحب بالحلقة؟')
                            ->required(),
                        Forms\Components\TextInput::make('circle_time')
                            ->label('وقت الحلقة')
                            ->required(),
                    ])->columns(2),

                Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('terms_accepted')
                            ->label('موافقة على الشروط')
                            ->required(),
                        Forms\Components\TextInput::make('store_link')
                            ->label('رابط المتجر')
                            ->maxLength(255),
                        Forms\Components\Select::make('support_status')
                            ->label('حالة الدعم')
                            ->required()
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'approved' => 'تم الموافقة',
                                'rejected' => 'مرفوض'
                            ]),
                        Forms\Components\Select::make('teacher_availability')
                            ->label('توفر المعلم')
                            ->required()
                            ->options([
                                'available' => 'متاح',
                                'not_available' => 'غير متاح',
                                'pending' => 'قيد البحث'
                            ]),
                    ])->columns(2),

                Section::make('حالة الطلب')
                    ->schema([
                        Forms\Components\DatePicker::make('launch_date')
                            ->label('تاريخ الإطلاق'),
                        Forms\Components\Toggle::make('is_launched')
                            ->label('تم الإطلاق')
                            ->required(),
                        Forms\Components\Select::make('request_status')
                            ->label('حالة الطلب')
                            ->required()
                            ->options([
                                'new' => 'جديد',
                                'in_progress' => 'قيد المعالجة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'completed' => 'مكتمل'
                            ]),
                        Forms\Components\TextInput::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('request_status') === 'rejected'),
                        Forms\Components\TextInput::make('days_since_submission')
                            ->label('أيام منذ التقديم')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requester_name')
                    ->label('مقدم الطلب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mosque_name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('الحي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('number_of_circles_requested')
                    ->label('عدد الحلقات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expected_students_number')
                    ->label('عدد الطلاب المتوقع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'in_progress' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_since_submission')
                    ->label('أيام منذ التقديم')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('request_status')
                    ->label('حالة الطلب')
                    ->options([
                        'new' => 'جديد',
                        'in_progress' => 'قيد المعالجة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'completed' => 'مكتمل'
                    ]),
                SelectFilter::make('support_status')
                    ->label('حالة الدعم')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'تمت الموافقة',
                        'rejected' => 'مرفوض'
                    ]),
                SelectFilter::make('is_launched')
                    ->label('تم الإطلاق')
                    ->options([
                        '1' => 'نعم',
                        '0' => 'لا'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('قبول')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn (CircleOpeningRequest $record) => $record->update(['request_status' => 'approved']))
                    ->visible(fn (CircleOpeningRequest $record) => $record->request_status !== 'approved' && $record->request_status !== 'completed'),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->form([
                        Forms\Components\TextInput::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required(),
                    ])
                    ->action(fn (CircleOpeningRequest $record, array $data) => $record->update([
                        'request_status' => 'rejected',
                        'rejection_reason' => $data['rejection_reason']
                    ]))
                    ->visible(fn (CircleOpeningRequest $record) => $record->request_status !== 'rejected'),
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
            RelationManagers\CircleRequestActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleOpeningRequests::route('/'),
            'create' => Pages\CreateCircleOpeningRequest::route('/create'),
            'edit' => Pages\EditCircleOpeningRequest::route('/{record}/edit'),
        ];
    }
    
    /**
     * إظهار عدد طلبات فتح الحلقات الجديدة وقيد المعالجة في مربع العدد
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('request_status', ['new', 'in_progress'])->count();
    }
    
    /**
     * تحديد لون مربع العدد (Badge) في القائمة حسب عدد الطلبات
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::whereIn('request_status', ['new', 'in_progress'])->count();
        
        if ($count > 5) {
            return 'warning';
        }
        
        return 'primary';
    }
}
