<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentInvoice extends EditRecord
{
    protected static string $resource = StudentInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
