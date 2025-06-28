<x-filament-panels::page>
    <div class="space-y-6">
        <!-- نموذج الإعدادات -->
        {{ $this->form }}
        
        <!-- أزرار الإجراءات -->
        <div class="flex justify-end space-x-2 space-x-reverse">
            {{ $this->saveAction }}
            {{ $this->testConnectionAction }}
            {{ $this->resetAction }}
        </div>
        
        <!-- قسم الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    الرسائل المرسلة اليوم
                </h3>
                <p class="text-3xl font-bold text-green-600">
                    {{ $this->getTodayMessagesCount() }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    إجمالي الرسائل
                </h3>
                <p class="text-3xl font-bold text-blue-600">
                    {{ $this->getTotalMessagesCount() }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    حالة الاتصال
                </h3>
                <p class="text-3xl font-bold {{ $this->getConnectionStatus() ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->getConnectionStatus() ? 'متصل' : 'غير متصل' }}
                </p>
            </div>
        </div>
        
        <!-- سجل الرسائل الأخيرة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                آخر الرسائل المرسلة
            </h3>
            
            @if($this->getRecentMessages()->isNotEmpty())
                <div class="space-y-3">
                    @foreach($this->getRecentMessages() as $message)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        إلى: {{ $message->to }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ Str::limit($message->message, 100) }}
                                    </p>
                                </div>
                                <div class="text-left">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $message->status === 'sent' ? 'bg-green-100 text-green-800' : 
                                           ($message->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $message->status === 'sent' ? 'مرسلة' : 
                                           ($message->status === 'failed' ? 'فشلت' : 'قيد الإرسال') }}
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $message->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    لا توجد رسائل حتى الآن
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
