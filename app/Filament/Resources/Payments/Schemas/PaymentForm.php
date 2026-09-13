<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_enrollment_id')->relationship('enrollment', 'id')->searchable()->preload()->required(),
                TextInput::make('payment_number')->label('Nomor pembayaran')->required(),
                TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->required(),
                Select::make('method')->options(['cash' => 'Tunai', 'transfer' => 'Transfer', 'virtual_account' => 'Virtual Account', 'qris' => 'QRIS', 'e_wallet' => 'E-Wallet', 'gateway' => 'Payment Gateway'])->required(),
                Select::make('status')->options(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'expired' => 'Expired', 'refunded' => 'Refunded'])->required(),
                DateTimePicker::make('paid_at')->label('Waktu dibayar'),
            ]);
    }
}
