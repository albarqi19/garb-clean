<?php

namespace App\Filament\Admin\Resources\NResource\Pages;

use App\Filament\Admin\Resources\NResource;
use Filament\Resources\Pages\Page;

class AttendanceReports extends Page
{
    protected static string $resource = NResource::class;

    protected static string $view = 'filament.admin.resources.n-resource.pages.attendance-reports';
}
