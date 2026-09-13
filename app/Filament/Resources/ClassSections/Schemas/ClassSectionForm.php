<?php

namespace App\Filament\Resources\ClassSections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_offering_id')->relationship('offering', 'id')->searchable()->preload()->required(),
                TextInput::make('code')->label('Kode kelas')->required(),
                TextInput::make('capacity')->numeric()->minValue(1)->required(),
                TextInput::make('room')->label('Ruangan'),
                Select::make('mode')->options(['offline' => 'Offline', 'online' => 'Online', 'hybrid' => 'Hybrid'])->required(),
            ]);
    }
}
