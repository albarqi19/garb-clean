<?php

namespace App\Filament\Components;

use Filament\Tables\Columns\Column;

class ProgressColumn extends Column
{
    protected string $view = 'filament.tables.columns.progress-column';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we always get numeric values
        $this->getStateUsing(function ($record, $column) {
            $attribute = $column->getName();
            $value = data_get($record, $attribute);
            
            // Convert to float, default to 0 if not numeric
            return is_numeric($value) ? (float)$value : 0;
        });
    }

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }
}
