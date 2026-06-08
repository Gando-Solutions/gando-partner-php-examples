<?php

declare(strict_types=1);

namespace App\EventListener;

use Gando\Partner\Symfony\Event\DepositActivated;
use Gando\Partner\Symfony\Event\DepositCancelled;
use Gando\Partner\Symfony\Event\DepositStatusChanged;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Example subscribers for gando/partner-symfony v0.1.4 typed webhook events.
 *
 * The bundled controller dispatches {@see WebhookReceived} first; the bundle maps it to typed events
 * (DepositActivated, DepositCancelled, …). Keep handlers fast — enqueue Messenger for slow work.
 */
final class GandoWebhookDemoListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onDepositStatusChanged(DepositStatusChanged $event): void
    {
        $webhook = $event->webhook;
        $depositId = $webhook->depositId() ?? 'unknown';
        $status = $webhook->depositStatus() ?? 'unknown';
        $previous = $webhook->previousDepositStatus() ?? 'n/a';

        $this->logger->info('Gando deposit status changed', [
            'deposit_id' => $depositId,
            'status' => $status,
            'previous_status' => $previous,
            'event' => $webhook->event,
        ]);

        if (in_array($status, ['cancelled', 'payment_issue'], true)) {
            $this->logger->warning('Deposit rejected or failed — release booking hold', [
                'deposit_id' => $depositId,
            ]);
        }
    }

    #[AsEventListener]
    public function onDepositActivated(DepositActivated $event): void
    {
        $webhook = $event->webhook;

        $this->logger->info('Gando deposit activated', [
            'deposit_id' => $webhook->depositId(),
            'status' => $webhook->depositStatus(),
            'rental_contract' => $webhook->rentalContract(),
            'amount_cents' => $webhook->amountCents(),
        ]);
    }

    #[AsEventListener]
    public function onDepositCancelled(DepositCancelled $event): void
    {
        $webhook = $event->webhook;

        $this->logger->warning('Gando deposit cancelled', [
            'deposit_id' => $webhook->depositId(),
            'previous_status' => $webhook->previousDepositStatus(),
        ]);
    }
}
