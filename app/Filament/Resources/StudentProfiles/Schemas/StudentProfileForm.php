<?php

namespace App\Filament\Resources\StudentProfiles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->relationship('user', 'name')->searchable()->preload(),
                TextInput::make('student_number')
                    ->required(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('national_id'),
                TextInput::make('gender'),
                DatePicker::make('birth_date'),
                TextInput::make('birth_place'),
                TextInput::make('address'),
                TextInput::make('photo_path'),
            ]);
    }
}
