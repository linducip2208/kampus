<?php

namespace App\Filament\Resources\Faculties\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FacultyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('university_id')->relationship('university', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->label('Nama fakultas')->required(),
                TextInput::make('code')->label('Kode')->required(),
            ]);
    }
}
