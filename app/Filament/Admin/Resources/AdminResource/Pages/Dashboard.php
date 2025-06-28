<?php

namespace App\Filament\Admin\Resources\AdminResource\Pages;

use App\Filament\Admin\Resources\AdminResource;
use Filament\Resources\Pages\Page;

class Dashboard extends Page
{
    protected static string $resource = AdminResource::class;

    protected static string $view = 'filament.admin.resources.admin-resource.pages.dashboard';
}
