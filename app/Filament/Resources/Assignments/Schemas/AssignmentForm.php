<?php

namespace App\Filament\Resources\Assignments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assignment')->schema([
                Select::make('class_section_id')->label('Kelas')->relationship('classSection', 'code')->searchable()->preload()->required(),
                TextInput::make('title')->label('Judul')->required(),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Terbit', 'open' => 'Dibuka', 'closed' => 'Ditutup', 'graded' => 'Dinilai'])->default('draft')->required(),
                Textarea::make('instructions')->label('Instruksi')->rows(5)->columnSpanFull(),
            ])->columns(2),
            Section::make('Jadwal & penilaian')->schema([
                DateTimePicker::make('opens_at')->label('Dibuka pada')->seconds(false),
                DateTimePicker::make('due_at')->label('Batas waktu')->seconds(false),
                Toggle::make('allow_late')->label('Terima terlambat'),
                TextInput::make('max_score')->label('Nilai maksimum')->numeric()->minValue(0)->maxValue(100)->default(100)->required(),
            ])->columns(2),
        ]);
    }
}
