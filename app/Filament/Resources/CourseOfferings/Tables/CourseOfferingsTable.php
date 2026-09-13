<?php

namespace App\Filament\Resources\CourseOfferings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CourseOfferingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('course.name')->label('Mata kuliah')->searchable(),
                TextColumn::make('semester.name')->label('Semester')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
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
