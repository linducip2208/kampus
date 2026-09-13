<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('immutable')->label('Audit log immutable')->content('Log dibuat otomatis oleh sistem dan tidak dapat diubah dari panel.'),
            ]);
    }
}
