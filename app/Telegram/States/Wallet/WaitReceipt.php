<?php

// app/Telegram/States/Wallet/WaitReceipt.php
namespace App\Telegram\States\Wallet;

use App\Jobs\Telegram\ArchiveTelegramPhotoJob;
use App\Services\Telegram\Admin\AdminMessenger;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\KeyboardFactory;
use Illuminate\Contracts\Config\Repository as Config;

class WaitReceipt extends AbstractState
{
    private string $cardNumber;
    private string $cardHolder;

    public function __construct(Config $config)
    {
        $this->cardNumber = (string) $config->get('payment.card_number');
        $this->cardHolder = (string) $config->get('payment.card_holder');
    }

    public function onEnter(): void
    {
        $amount = (int) $this->getData('topup_amount', 0);
        $formattedAmount = number_format($amount);

        $this->sendT(
            'telegram.wallet.manual_topup_instructions',
            [
                'amount' => $formattedAmount,
                'card_number' => $this->cardNumber,
                'card_holder' => $this->cardHolder,
            ],
            KeyboardFactory::replyBackOnly()
        );
    }

    public function onPhoto(array $photos, array $u): void
    {
        $last  = $photos[array_key_last($photos)] ?? null;
        $fileId= $last['file_id'] ?? null;
        $uniq  = $last['file_unique_id'] ?? null;

        if (!$fileId) { $this->send(__('telegram.wallet.invalid_photo'), \App\Telegram\UI\KeyboardFactory::replyBackOnly()); return; }

        $user = $this->process();
        $amount = (int)$this->getData('topup_amount', 0);

        $req = \App\Models\TopupRequest::create([
            'user_id'=>$user->id,'method'=>'card','amount'=>$amount,'currency'=>'IRT','status'=>'pending',
            'receipt_file_id'=>$fileId,
        ]);

        ArchiveTelegramPhotoJob::dispatch(
            userId:$user->id, tgFileId:$fileId, tgUniqueId:$uniq, purpose:'receipt',
            mediableType:\App\Models\TopupRequest::class, mediableId:$req->id
        );

        app(AdminMessenger::class)->broadcastTopupRequest($req);

        $this->send(__('telegram.wallet.received'), \App\Telegram\UI\KeyboardFactory::replyBackOnly());
        $this->resetToWelcomeMenu($resetAnchor = false);
    }

    protected function onText(string $text, array $u): void
    {
        $this->send(__('telegram.wallet.invalid_photo'), KeyboardFactory::replyBackOnly());
    }

    protected function onDocument(array $doc, array $u): void
    {
        $this->send(__('telegram.wallet.invalid_photo'), KeyboardFactory::replyBackOnly());
    }
}
