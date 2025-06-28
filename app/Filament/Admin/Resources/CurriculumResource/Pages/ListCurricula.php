<?php

namespace App\Filament\Admin\Resources\CurriculumResource\Pages;

use App\Filament\Admin\Resources\CurriculumResource;
use App\Services\CurriculumTemplateService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListCurricula extends ListRecords
{
    protected static string $resource = CurriculumResource::class;

    protected function getHeaderActions(): array
    {
        $templateService = new CurriculumTemplateService();
        $templates = $templateService::getAvailableTemplates();
        
        return [
            Actions\CreateAction::make()
                ->label('إنشاء منهج جديد'),
                
            Actions\Action::make('import_template')
                ->label('استيراد قالب جاهز')
                ->icon('heroicon-o-rectangle-stack')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('template_type')
                        ->label('نوع القالب')
                        ->options(collect($templates)->mapWithKeys(function ($template, $key) {
                            return [$key => $template['name']];
                        })->toArray())
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) use ($templates) {
                            if ($state && isset($templates[$state])) {
                                $template = $templates[$state];
                                $set('template_description', $template['description']);
                                $set('template_duration', $template['duration']);
                                $set('template_suitable_for', $template['suitable_for']);
                            }
                        }),
                    
                    Forms\Components\Placeholder::make('template_info')
                        ->label('معلومات القالب')
                        ->content(function (callable $get) use ($templates) {
                            $templateType = $get('template_type');
                            if (!$templateType || !isset($templates[$templateType])) {
                                return 'اختر قالب لعرض المعلومات';
                            }
                            
                            $template = $templates[$templateType];
                            return "**الوصف:** {$template['description']}\n\n**المدة:** {$template['duration']}\n\n**مناسب لـ:** {$template['suitable_for']}";
                        })
                        ->visible(fn (callable $get) => !empty($get('template_type'))),
                    
                    Forms\Components\TextInput::make('custom_name')
                        ->label('اسم المنهج المخصص')
                        ->placeholder('اتركه فارغاً لاستخدام الاسم الافتراضي')
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    try {
                        $curriculum = CurriculumTemplateService::createFromTemplate(
                            $data['template_type'],
                            $data['custom_name'] ?? null
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('تم إنشاء المنهج بنجاح')
                            ->body("تم إنشاء المنهج '{$curriculum->name}' من القالب المحدد.")
                            ->send();
                            
                        return redirect()->to(CurriculumResource::getUrl('edit', ['record' => $curriculum]));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('فشل في إنشاء المنهج')
                            ->body('حدث خطأ أثناء إنشاء المنهج من القالب: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
