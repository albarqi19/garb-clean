<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WhatsAppMessageResource\Pages;
use App\Models\WhatsAppMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'رسائل WhatsApp';
    protected static ?string $modelLabel = 'رسالة WhatsApp';
    protected static ?string $pluralModelLabel = 'رسائل WhatsApp';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الرسالة')
                    ->schema([
                        Forms\Components\Select::make('user_type')
                            ->label('نوع المستلم')
                            ->options([
                                'teacher' => 'معلم',
                                'student' => 'طالب',
                                'parent' => 'ولي أمر',
                                'admin' => 'إداري',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('user_id')
                            ->label('معرف المستخدم')
                            ->numeric(),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required(),

                        Forms\Components\Select::make('message_type')
                            ->label('نوع الرسالة')
                            ->options([
                                'welcome' => 'ترحيب',
                                'notification' => 'إشعار',
                                'attendance' => 'حضور',
                                'session' => 'جلسة',
                                'reminder' => 'تذكير',
                                'test' => 'اختبار',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'في الانتظار',
                                'sent' => 'تم الإرسال',
                                'failed' => 'فشل',
                                'skipped' => 'تم التجاهل',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('محتوى الرسالة')
                    ->schema([
                        Forms\Components\Textarea::make('message_content')
                            ->label('نص الرسالة')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('template_data')
                            ->label('بيانات القالب (JSON)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('{"key": "value"}'),
                    ]),

                Forms\Components\Section::make('معلومات الإرسال')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('معرف خارجي')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('تاريخ الإرسال')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('failed_at')
                            ->label('تاريخ الفشل')
                            ->disabled(),

                        Forms\Components\Textarea::make('error_message')
                            ->label('رسالة الخطأ')
                            ->rows(2)
                            ->disabled(),

                        Forms\Components\Textarea::make('api_response')
                            ->label('استجابة API')
                            ->rows(3)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('المعرف')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user_type')
                    ->label('نوع المستلم')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'teacher' => 'معلم',
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                        'admin' => 'إداري',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'teacher' => 'info',
                        'student' => 'success',
                        'parent' => 'warning',
                        'admin' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ الرقم')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('message_type')
                    ->label('نوع الرسالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'welcome' => 'ترحيب',
                        'notification' => 'إشعار',
                        'attendance' => 'حضور',
                        'session' => 'جلسة',
                        'reminder' => 'تذكير',
                        'test' => 'اختبار',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                        'skipped' => 'تم التجاهل',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('message_content')
                    ->label('محتوى الرسالة')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('لم يتم الإرسال'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_error')
                    ->label('خطأ')
                    ->boolean()
                    ->getStateUsing(fn (WhatsAppMessage $record): bool => !empty($record->error_message))
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                        'skipped' => 'تم التجاهل',
                    ])
                    ->multiple(),

                SelectFilter::make('user_type')
                    ->label('نوع المستلم')
                    ->options([
                        'teacher' => 'معلم',
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                        'admin' => 'إداري',
                    ])
                    ->multiple(),

                SelectFilter::make('message_type')
                    ->label('نوع الرسالة')
                    ->options([
                        'welcome' => 'ترحيب',
                        'notification' => 'إشعار',
                        'attendance' => 'حضور',
                        'session' => 'جلسة',
                        'reminder' => 'تذكير',
                        'test' => 'اختبار',
                    ])
                    ->multiple(),

                Filter::make('sent_today')
                    ->label('تم إرسالها اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('sent_at', today()))
                    ->toggle(),

                Filter::make('failed_messages')
                    ->label('الرسائل الفاشلة')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed'))
                    ->toggle(),

                Filter::make('pending_messages')
                    ->label('الرسائل المعلقة')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->toggle(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'من تاريخ: ' . $data['created_from'];
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'إلى تاريخ: ' . $data['created_until'];
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\Action::make('resend')
                    ->label('إعادة إرسال')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (WhatsAppMessage $record): bool => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppMessage $record) {
                        try {
                            // إعادة تعيين حالة الرسالة وإرسالها مرة أخرى
                            $record->update([
                                'status' => 'pending',
                                'error_message' => null,
                                'failed_at' => null,
                            ]);

                            // إرسال الرسالة عبر Job
                            \App\Jobs\SendWhatsAppMessage::dispatch($record->id);

                            \Filament\Notifications\Notification::make()
                                ->title('تم الإرسال للمعالجة')
                                ->body('تم إضافة الرسالة إلى قائمة الانتظار للإرسال.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('خطأ في إعادة الإرسال')
                                ->body('حدث خطأ: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])            ->headerActions([
                Tables\Actions\ActionGroup::make([
                        Tables\Actions\Action::make('show_status')
                            ->label('عرض حالة القائمة')
                            ->icon('heroicon-o-chart-bar')
                            ->action(function () {
                                $pendingCount = WhatsAppMessage::where('status', 'pending')->count();
                                $failedCount = WhatsAppMessage::where('status', 'failed')->count();                                $queueJobs = DB::table('jobs')->count();
                                $failedJobs = DB::table('failed_jobs')->count();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('حالة رسائل الواتساب')
                                    ->body("الرسائل المعلقة: {$pendingCount} | الرسائل الفاشلة: {$failedCount} | المهام المعلقة: {$queueJobs} | المهام الفاشلة: {$failedJobs}")
                                    ->info()
                                    ->duration(10000)
                                    ->send();
                            }),
                        
                        Tables\Actions\Action::make('retry_failed_messages')
                            ->label('إعادة محاولة الرسائل الفاشلة')
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('إعادة محاولة الرسائل الفاشلة')
                            ->modalDescription('سيتم إعادة إضافة جميع الرسائل الفاشلة إلى قائمة الانتظار للإرسال مرة أخرى.')
                            ->action(function () {
                                try {
                                    $failedMessages = WhatsAppMessage::where('status', 'failed')->get();
                                    $count = $failedMessages->count();
                                    
                                    if ($count === 0) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('لا توجد رسائل فاشلة')
                                            ->body('لا توجد رسائل فاشلة لإعادة المحاولة.')
                                            ->info()
                                            ->send();
                                        return;
                                    }
                                    
                                    foreach ($failedMessages as $message) {
                                        $message->update([
                                            'status' => 'pending',
                                            'error_message' => null,
                                            'failed_at' => null,
                                        ]);
                                        \App\Jobs\SendWhatsAppMessage::dispatch($message->id);
                                    }
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('تم إعادة المحاولة')
                                        ->body("تم إعادة إضافة {$count} رسالة فاشلة إلى قائمة الانتظار.")
                                        ->success()
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('خطأ في إعادة المحاولة')
                                        ->body('حدث خطأ: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        
                        Tables\Actions\Action::make('clear_queue')
                            ->label('مسح قائمة الانتظار')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('مسح قائمة الانتظار')
                            ->modalDescription('تحذير! سيتم مسح جميع المهام المعلقة والفاشلة من قائمة الانتظار. هذا الإجراء لا يمكن التراجع عنه.')
                            ->action(function () {
                                try {                                    $pendingJobs = DB::table('jobs')->count();
                                    $failedJobs = DB::table('failed_jobs')->count();
                                    
                                    // مسح الوظائف المعلقة والفاشلة
                                    DB::table('jobs')->truncate();
                                    DB::table('failed_jobs')->truncate();
                                    
                                    // إعادة تشغيل queue
                                    Artisan::call('queue:restart');
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('تم مسح قائمة الانتظار')
                                        ->body("تم مسح {$pendingJobs} مهمة معلقة و {$failedJobs} مهمة فاشلة وإعادة تشغيل النظام.")
                                        ->success()
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('خطأ في مسح القائمة')
                                        ->body('حدث خطأ: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        
                        Tables\Actions\Action::make('process_pending')
                            ->label('معالجة الرسائل المعلقة')
                            ->icon('heroicon-o-play')
                            ->color('success')
                            ->action(function () {
                                try {
                                    $pendingMessages = WhatsAppMessage::where('status', 'pending')->get();
                                    $count = $pendingMessages->count();
                                    
                                    if ($count === 0) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('لا توجد رسائل معلقة')
                                            ->body('لا توجد رسائل معلقة للمعالجة.')
                                            ->info()
                                            ->send();
                                        return;
                                    }
                                    
                                    foreach ($pendingMessages as $message) {
                                        \App\Jobs\SendWhatsAppMessage::dispatch($message->id);
                                    }
                                      // تشغيل معالج قائمة الانتظار مرة واحدة
                                    Artisan::call('queue:work', ['--once' => true, '--timeout' => 60]);
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('تم بدء المعالجة')
                                        ->body("تم إضافة {$count} رسالة معلقة إلى قائمة الانتظار وبدء المعالجة.")
                                        ->success()
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('خطأ في المعالجة')
                                        ->body('حدث خطأ: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        
                        Tables\Actions\Action::make('restart_queue')
                            ->label('إعادة تشغيل النظام')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function () {
                                try {
                                    Artisan::call('queue:restart');
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('تم إعادة التشغيل')
                                        ->body('تم إعادة تشغيل نظام قائمة الانتظار بنجاح.')
                                        ->success()
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('خطأ في إعادة التشغيل')
                                        ->body('حدث خطأ: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }                            }),
                    ])
                    ->label('إدارة الرسائل العالقة')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('info')
                    ->button(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppMessages::route('/'),
            'create' => Pages\CreateWhatsAppMessage::route('/create'),
            'view' => Pages\ViewWhatsAppMessage::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'failed')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'failed')->count() > 0 ? 'danger' : null;
    }
}
