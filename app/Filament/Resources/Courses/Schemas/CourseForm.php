<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('university_id')->relationship('university', 'name')->searchable()->preload()->required(),
                TextInput::make('code')->label('Kode mata kuliah')->required(),
                TextInput::make('name')->label('Nama mata kuliah')->required(),
                TextInput::make('theory_credits')->label('SKS teori')->numeric()->minValue(0)->required(),
                TextInput::make('practical_credits')->label('SKS praktikum')->numeric()->minValue(0)->required(),
                TextInput::make('recommended_term')->label('Semester rekomendasi')->numeric()->minValue(1),
            ]);
    }
}
