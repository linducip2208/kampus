<?php

namespace App\Filament\Resources\Applicants\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas calon mahasiswa')->schema([
                Select::make('university_id')->relationship('university', 'name')->searchable()->preload()->required(),
                Select::make('admission_path_id')->label('Jalur masuk')->relationship('admissionPath', 'name')->searchable()->preload(),
                TextInput::make('registration_number')->label('Nomor pendaftaran')->required()->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama lengkap')->required(),
                TextInput::make('national_id')->label('NIK')->maxLength(16),
                TextInput::make('nisn')->label('NISN'),
                TextInput::make('email')->email()->required(),
                TextInput::make('phone')->label('Nomor HP'),
                TextInput::make('whatsapp')->label('WhatsApp'),
                TextInput::make('birth_place')->label('Tempat lahir'),
                DatePicker::make('birth_date')->label('Tanggal lahir'),
                Select::make('gender')->label('Jenis kelamin')->options(['male' => 'Laki-laki', 'female' => 'Perempuan']),
                Textarea::make('address')->label('Alamat')->columnSpanFull(),
            ])->columns(2),
            Section::make('Riwayat pendidikan')->schema([
                TextInput::make('previous_school')->label('Sekolah asal'),
                TextInput::make('graduation_year')->label('Tahun lulus')->numeric()->minValue(1900)->maxValue(2100),
                TextInput::make('school_score')->label('Nilai sekolah')->numeric()->minValue(0)->maxValue(100),
            ])->columns(3),
        ]);
    }
}
