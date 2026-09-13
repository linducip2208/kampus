<?php

namespace App\Filament\Resources\Semesters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year_id')->relationship('academicYear', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->label('Nama semester')->required(),
                TextInput::make('code')->label('Kode semester')->required(),
                Select::make('term')->options(['odd' => 'Ganjil', 'even' => 'Genap', 'short' => 'Antara'])->required(),
                DatePicker::make('starts_on')->label('Mulai')->required(),
                DatePicker::make('ends_on')->label('Selesai')->required(),
                Toggle::make('is_active')->label('Aktif'),
            ]);
    }
}
