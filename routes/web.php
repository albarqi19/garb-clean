<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// مسارات المهام التسويقية
Route::prefix('marketing-tasks')->middleware('auth')->group(function () {
    Route::get('/', 'App\Http\Controllers\MarketingTaskController@index');
    Route::post('/', 'App\Http\Controllers\MarketingTaskController@store');
    Route::get('/{marketingTask}', 'App\Http\Controllers\MarketingTaskController@show');
    Route::put('/{marketingTask}', 'App\Http\Controllers\MarketingTaskController@update');
    Route::delete('/{marketingTask}', 'App\Http\Controllers\MarketingTaskController@destroy');
    Route::post('/{marketingTask}/toggle-complete', 'App\Http\Controllers\MarketingTaskController@toggleComplete');
    Route::post('/create-next-week', 'App\Http\Controllers\MarketingTaskController@createNextWeek');
    Route::post('/create-default-tasks', 'App\Http\Controllers\MarketingTaskController@createDefaultTasks');
    Route::get('/statistics', 'App\Http\Controllers\MarketingTaskController@getStatistics');
});

// مسارات الخطة الاستراتيجية
Route::prefix('strategic')->middleware('auth')->name('strategic.')->group(function () {
    // لوحة معلومات الاستراتيجية
    Route::get('dashboard', 'App\Http\Controllers\StrategicPlanController@dashboard')->name('dashboard');
    
    // مسارات الخطط الاستراتيجية
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', 'App\Http\Controllers\StrategicPlanController@index')->name('index');
        Route::get('/create', 'App\Http\Controllers\StrategicPlanController@create')->name('create');
        Route::post('/', 'App\Http\Controllers\StrategicPlanController@store')->name('store');
        Route::get('/{strategicPlan}', 'App\Http\Controllers\StrategicPlanController@show')->name('show');
        Route::get('/{strategicPlan}/edit', 'App\Http\Controllers\StrategicPlanController@edit')->name('edit');
        Route::put('/{strategicPlan}', 'App\Http\Controllers\StrategicPlanController@update')->name('update');
        Route::delete('/{strategicPlan}', 'App\Http\Controllers\StrategicPlanController@destroy')->name('destroy');
        Route::post('/{strategicPlan}/toggle-active', 'App\Http\Controllers\StrategicPlanController@toggleActive')->name('toggle-active');
    });
    
    // مسارات المؤشرات الاستراتيجية
    Route::prefix('indicators')->name('indicators.')->group(function () {
        Route::get('/', 'App\Http\Controllers\StrategicIndicatorController@index')->name('index');
        Route::get('/create', 'App\Http\Controllers\StrategicIndicatorController@create')->name('create');
        Route::post('/', 'App\Http\Controllers\StrategicIndicatorController@store')->name('store');
        Route::get('/{strategicIndicator}', 'App\Http\Controllers\StrategicIndicatorController@show')->name('show');
        Route::get('/{strategicIndicator}/edit', 'App\Http\Controllers\StrategicIndicatorController@edit')->name('edit');
        Route::put('/{strategicIndicator}', 'App\Http\Controllers\StrategicIndicatorController@update')->name('update');
        Route::delete('/{strategicIndicator}', 'App\Http\Controllers\StrategicIndicatorController@destroy')->name('destroy');
        Route::get('/{strategicIndicator}/report', 'App\Http\Controllers\StrategicIndicatorController@report')->name('report');
    });
    
    // مسارات عمليات الرصد
    Route::prefix('monitorings')->name('monitorings.')->group(function () {
        Route::get('/', 'App\Http\Controllers\StrategicMonitoringController@index')->name('index');
        Route::get('/create', 'App\Http\Controllers\StrategicMonitoringController@create')->name('create');
        Route::post('/', 'App\Http\Controllers\StrategicMonitoringController@store')->name('store');
        Route::get('/{strategicMonitoring}', 'App\Http\Controllers\StrategicMonitoringController@show')->name('show');
        Route::get('/{strategicMonitoring}/edit', 'App\Http\Controllers\StrategicMonitoringController@edit')->name('edit');
        Route::put('/{strategicMonitoring}', 'App\Http\Controllers\StrategicMonitoringController@update')->name('update');
        Route::delete('/{strategicMonitoring}', 'App\Http\Controllers\StrategicMonitoringController@destroy')->name('destroy');
    });
    
    // مسارات المبادرات الاستراتيجية
    Route::prefix('initiatives')->name('initiatives.')->group(function () {
        Route::get('/', 'App\Http\Controllers\StrategicInitiativeController@index')->name('index');
        Route::get('/create', 'App\Http\Controllers\StrategicInitiativeController@create')->name('create');
        Route::post('/', 'App\Http\Controllers\StrategicInitiativeController@store')->name('store');
        Route::get('/{strategicInitiative}', 'App\Http\Controllers\StrategicInitiativeController@show')->name('show');
        Route::get('/{strategicInitiative}/edit', 'App\Http\Controllers\StrategicInitiativeController@edit')->name('edit');
        Route::put('/{strategicInitiative}', 'App\Http\Controllers\StrategicInitiativeController@update')->name('update');
        Route::delete('/{strategicInitiative}', 'App\Http\Controllers\StrategicInitiativeController@destroy')->name('destroy');
        Route::post('/{strategicInitiative}/update-status', 'App\Http\Controllers\StrategicInitiativeController@updateStatus')->name('update-status');
    });
});
