<?php

namespace App\Filament\Resources\StudentInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_enrollment_id')->relationship('enrollment', 'id')->searchable()->preload()->required(),
                Select::make('semester_id')->relationship('semester', 'name')->searchable()->preload(),
                TextInput::make('invoice_number')->label('Nomor invoice')->required(),
                TextInput::make('total_amount')->label('Total')->numeric()->prefix('Rp')->required(),
                TextInput::make('paid_amount')->label('Terbayar')->numeric()->prefix('Rp')->disabled()->dehydrated(false),
                Select::make('status')->options(['draft' => 'Draft', 'issued' => 'Terbit', 'partial' => 'Sebagian', 'paid' => 'Lunas', 'overdue' => 'Jatuh tempo', 'cancelled' => 'Dibatalkan'])->required(),
                DatePicker::make('due_on')->label('Jatuh tempo'),
            ]);
    }
}
