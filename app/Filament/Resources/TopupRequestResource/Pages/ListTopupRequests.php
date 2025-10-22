<?php

namespace App\Filament\Resources\TopupRequestResource\Pages;

use App\Filament\Resources\TopupRequestResource\TopupRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListTopupRequests extends ListRecords
{
    protected static string $resource = TopupRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
