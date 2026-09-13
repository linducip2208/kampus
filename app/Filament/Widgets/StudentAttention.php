<?php

namespace App\Filament\Widgets;

use App\Models\StudentEnrollment;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentAttention extends TableWidget
{
    use DashboardWidgetFilter;
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Mahasiswa yang perlu perhatian';
    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table->query(StudentEnrollment::with(['studentProfile', 'studyProgram'])->whereIn('status', ['leave', 'suspended', 'inactive'])->latest())->columns([
            TextColumn::make('studentProfile.student_number')->label('NIM'), TextColumn::make('studentProfile.full_name')->label('Mahasiswa'), TextColumn::make('studyProgram.name')->label('Program studi'), TextColumn::make('status')->badge(),
        ]);
    }
}
