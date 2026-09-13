<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentInvoice extends CreateRecord
{
    protected static string $resource = StudentInvoiceResource::class;
}
