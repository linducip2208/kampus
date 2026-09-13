<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('university_id')->relationship('university', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->label('Nama tahun akademik')->required(),
                TextInput::make('start_year')->label('Tahun mulai')->numeric()->required(),
                TextInput::make('end_year')->label('Tahun selesai')->numeric()->required(),
                Toggle::make('is_active')->label('Aktif'),
            ]);
    }
}
