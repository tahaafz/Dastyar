<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTickets\SupportTicketResponder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message')
                    ->label(__('Question'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('answer.message')
                    ->label(__('Answer'))
                    ->wrap()
                    ->placeholder(__('Pending reply')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('reply')
                    ->label(__('Reply'))
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Textarea::make('message')
                            ->label(__('Answer'))
                            ->rows(6)
                            ->required(),
                    ])
                    ->visible(fn (SupportTicket $record) => !$record->is_answered)
                    ->action(function (SupportTicket $record, array $data) {
                        $admin = auth()->user();

                        if (!$admin instanceof User) {
                            throw ValidationException::withMessages([
                                'message' => __('Only authenticated admins can reply to support tickets.'),
                            ]);
                        }

                        app(SupportTicketResponder::class)->respond($record, $admin, $data['message']);

                        $record->refresh();
                    })
                    ->successNotificationTitle(__('Reply sent to the user.')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
