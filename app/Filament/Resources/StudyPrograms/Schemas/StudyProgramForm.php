<?php

namespace App\Filament\Resources\StudyPrograms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudyProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')->relationship('department', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->label('Nama program studi')->required(),
                TextInput::make('code')->label('Kode')->required(),
                TextInput::make('level')->label('Jenjang')->required()->placeholder('S1 / S2 / D3'),
                TextInput::make('degree')->label('Gelar'),
                TextInput::make('accreditation')->label('Akreditasi'),
                TextInput::make('capacity')->label('Kapasitas')->numeric()->minValue(1),
            ]);
    }
}
