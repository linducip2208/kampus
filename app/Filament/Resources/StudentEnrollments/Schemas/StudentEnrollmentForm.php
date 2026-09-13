<?php

namespace App\Filament\Resources\StudentEnrollments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_profile_id')->relationship('studentProfile', 'full_name')->searchable()->preload()->required(),
                Select::make('study_program_id')->relationship('studyProgram', 'name')->searchable()->preload()->required(),
                Select::make('curriculum_id')->relationship('curriculum', 'name')->searchable()->preload(),
                Select::make('advisor_id')->relationship('advisor', 'nidn')->searchable()->preload(),
                TextInput::make('cohort')->numeric()->required(),
                Select::make('status')->options(['active' => 'Aktif', 'leave' => 'Cuti', 'inactive' => 'Nonaktif', 'suspended' => 'Ditangguhkan', 'graduated' => 'Lulus'])->required(),
                DatePicker::make('enrolled_on')->label('Tanggal masuk'),
            ]);
    }
}
