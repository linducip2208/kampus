<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')->label('Event')->searchable()->sortable(),
                TextColumn::make('module')->label('Modul')->searchable(),
                TextColumn::make('entity_type')->label('Entitas')->searchable(),
                TextColumn::make('user.name')->label('Pengguna')->searchable(),
                TextColumn::make('ip_address')->label('IP'),
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
