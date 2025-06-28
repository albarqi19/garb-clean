<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns\HasName;
use Filament\Support\Concerns\HasExtraAttributes;

class PlanImportGrid extends Component
{
    use HasName;
    use HasExtraAttributes;
    
    protected string $view = 'filament.components.plan-import-grid';
    
    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }
}
