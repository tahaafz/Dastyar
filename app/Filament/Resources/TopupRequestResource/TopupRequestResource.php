<?php

namespace App\Filament\Resources\TopupRequestResource;

use App\Models\TopupRequest;
use App\Models\User;
use App\Services\Telegram\TopupApprovalService;
use BackedEnum;
use InvalidArgumentException;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class TopupRequestResource extends Resource
{
    protected static ?string $model = TopupRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static null|string|UnitEnum $navigationGroup = 'مالی';
    protected static ?string $navigationLabel = 'درخواست‌های افزایش موجودی (دارای تصویر)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('receipt_media_id')
                    ->orWhereNotNull('receipt_file_id');
            })
            ->when(
                Schema::hasColumn((new TopupRequest())->getTable(), 'status'),
                fn (Builder $query) => $query->where('status', 'pending')
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.name')->label('کاربر')->searchable()->toggleable(),
                TextColumn::make('amount')->label('مبلغ')->numeric()->sortable(),
                TextColumn::make('currency')->label('ارز')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('method')->label('روش')->toggleable(),
                ImageColumn::make('receiptMedia.path')
                    ->label('رسید')
                    ->height(96)->width(96)
                    ->circular(false)
                    ->getStateUsing(function (TopupRequest $record) {
                        return static::receiptUrl($record);
                    })
                    ->extraAttributes(['class' => 'cursor-pointer'])
                    ->action(static::reviewAction()),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),
                TextColumn::make('created_at')->label('تاریخ')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending'  => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ])
                    ->default('pending')
                    ->visible(fn () => Schema::hasColumn((new TopupRequest())->getTable(), 'status')),
            ])
            ->actions([
                static::reviewAction(),
                Action::make('approve')
                    ->label('تایید')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (TopupRequest $record) => $record->status === 'pending' || $record->status === null)
                    ->requiresConfirmation()
                    ->action(function (TopupRequest $record) {
                        static::processTopupAction($record, 'approve');
                    }),
                Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (TopupRequest $record) => $record->status === 'pending' || $record->status === null)
                    ->requiresConfirmation()
                    ->action(function (TopupRequest $record) {
                        static::processTopupAction($record, 'reject');
                    }),
            ])
            ->bulkActions([]);
    }

    protected static function reviewAction(): Action
    {
        return Action::make('review')
            ->label('بررسی رسید')
            ->icon('heroicon-o-eye')
            ->modalHeading('بررسی رسید')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('4xl')
            ->modalContent(function (TopupRequest $record) {
                $url   = static::receiptUrl($record);
                $img   = $url ? "<img src=\"{$url}\" alt=\"receipt\" class=\"w-full h-auto rounded-xl\">" : "<div class=\"text-center py-8\">تصویر در دسترس نیست</div>";
                $meta  = "<div class=\"text-sm text-gray-600 dark:text-gray-300 text-center mt-4\">
                    <div>کاربر: <strong>".e(optional($record->user)->name ?? '-')."</strong></div>
                    <div>مبلغ: <strong>".number_format((int)$record->amount)." ".e($record->currency ?? 'IRR')."</strong></div>
                    <div>روش: <strong>".e($record->method ?? '-')."</strong></div>
                    <div>تاریخ: <strong>".optional($record->created_at)->format('Y-m-d H:i')."</strong></div>
                </div>";
                return new HtmlString("<div class=\"space-y-4\">{$img}{$meta}</div>");
            })
            ->modalFooterActions([
                Action::make('approveModal')
                    ->label('تایید')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (TopupRequest $record) {
                        static::processTopupAction($record, 'approve');
                    })
                    ->close(),
                Action::make('rejectModal')
                    ->label('رد')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (TopupRequest $record) {
                        static::processTopupAction($record, 'reject');
                    })
                    ->close(),
                Action::make('cancel')
                    ->label('بستن')
                    ->color('gray')
                    ->close(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopupRequests::route('/'),
        ];
    }

    protected static function notifySuccess(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    protected static function notifyError(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    protected static function receiptUrl(TopupRequest $record): ?string
    {
        $media = $record->receiptMedia;

        if ($media) {
            $path = $media->fullPath();

            if ($path) {
                $diskName = $media->driver ?: 'media';

                if (!config("filesystems.disks.{$diskName}")) {
                    $diskName = 'public';
                }

                try {
                    $disk = Storage::disk($diskName);
                } catch (InvalidArgumentException) {
                    $disk = Storage::disk('public');
                }

                if ($disk->exists($path)) {
                    return $disk->url($path);
                }
            }
        }

        if (!empty($record->receipt_file_id) && Route::has('admin.topup.receipt.proxy')) {
            return route('admin.topup.receipt.proxy', $record);
        }

        return null;
    }

    protected static function processTopupAction(TopupRequest $record, string $action): void
    {
        $admin = auth()->user();

        if (!$admin instanceof User || !$admin->is_admin) {
            static::notifyError('دسترسی مجاز نیست.');
            return;
        }

        $currentStatus = $record->status;
        if ($currentStatus !== null && $currentStatus !== 'pending') {
            static::notifyError('این درخواست قبلاً بررسی شده است.');
            return;
        }

        app(TopupApprovalService::class)->handle($admin, $action, $record->getKey());

        $record->refresh();

        if ($record->status === 'approved' && $action === 'approve') {
            static::notifySuccess('درخواست تایید شد و موجودی کاربر افزایش یافت.');
        } elseif ($record->status === 'rejected' && $action === 'reject') {
            static::notifySuccess('درخواست رد شد و به کاربر اطلاع داده شد.');
        } else {
            static::notifyError('عدم موفقیت در بروزرسانی درخواست.');
        }
    }
}
