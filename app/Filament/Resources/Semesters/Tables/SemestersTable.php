<?php

namespace App\Filament\Resources\Semesters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SemestersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Semester')->searchable()->sortable(),
                TextColumn::make('academicYear.name')->label('Tahun akademik')->searchable(),
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('term')->label('Periode')->badge(),
                TextColumn::make('starts_on')->label('Mulai')->date()->sortable(),
                TextColumn::make('ends_on')->label('Selesai')->date()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
