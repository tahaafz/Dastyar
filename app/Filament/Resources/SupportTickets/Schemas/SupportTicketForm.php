<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('reply_to_id')
                    ->numeric(),
                Select::make('type')
                    ->options(SupportTicketType::class)
                    ->default('question')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_answered')
                    ->required(),
                DateTimePicker::make('answered_at'),
            ]);
    }
}
