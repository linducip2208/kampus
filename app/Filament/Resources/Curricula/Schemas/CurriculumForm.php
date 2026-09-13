<?php

namespace App\Filament\Resources\Curricula\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurriculumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('study_program_id')->relationship('studyProgram', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->label('Nama kurikulum')->required(),
                TextInput::make('year')->label('Tahun')->numeric()->required(),
                TextInput::make('minimum_credits')->label('Minimum SKS')->numeric()->required(),
            ]);
    }
}
