<?php

namespace App\Filament\Resources\Universities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UniversityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama universitas')->required()->maxLength(180),
                TextInput::make('short_name')->label('Singkatan')->maxLength(30),
                TextInput::make('code')->label('Kode')->required()->maxLength(20),
                TextInput::make('email')->email()->maxLength(180),
                TextInput::make('phone')->label('Telepon')->maxLength(30),
                TextInput::make('website')->url()->maxLength(180),
                TextInput::make('timezone')->default('Asia/Jakarta')->required(),
            ]);
    }
}
