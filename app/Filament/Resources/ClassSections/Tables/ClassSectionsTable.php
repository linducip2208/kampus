<?php

namespace App\Filament\Resources\ClassSections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ClassSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kelas')->searchable()->sortable(),
                TextColumn::make('offering.course.code')->label('Mata kuliah')->searchable(),
                TextColumn::make('offering.semester.name')->label('Semester')->searchable(),
                TextColumn::make('capacity')->label('Kapasitas')->sortable(),
                TextColumn::make('room')->label('Ruangan'),
                TextColumn::make('mode')->label('Mode')->badge(),
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
