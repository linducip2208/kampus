<?php

namespace App\Filament\Resources\StudentEnrollments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('studentProfile.student_number')->label('NIM')->searchable()->sortable(),
                TextColumn::make('studentProfile.full_name')->label('Mahasiswa')->searchable(),
                TextColumn::make('studyProgram.name')->label('Program studi')->searchable(),
                TextColumn::make('cohort')->label('Angkatan')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
