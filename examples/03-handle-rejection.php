<?php

declare(strict_types=1);

/**
 * Handle deposit rejection paths: return URL query params and deposit.status_changed webhooks.
 *
 * Usage:
 *   php examples/03-handle-rejection.php
 *   php examples/03-handle-rejection.php --return-url '?depositId=dep_x&depositStatus=declined'
 */

require __DIR__.'/_bootstrap.php';

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;

$returnUrlQuery = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--return-url=')) {
        $returnUrlQuery = substr($arg, strlen('--return-url='));
    }
}

echo "=== A) Return URL after inline redirect ===\n";
demo_return_url($returnUrlQuery ?? '?depositId=dep_example&depositStatus=declined');

echo "\n=== B) Webhook: deposit.status_changed (rejection) ===\n";
demo_rejection_webhook();

$depositId = $_ENV['GANDO_DEPOSIT_ID'] ?? getenv('GANDO_DEPOSIT_ID');
if (is_string($depositId) && $depositId !== '') {
    echo "\n=== C) Staging retrieve: {$depositId} ===\n";
    demo_staging_retrieve($depositId);
} else {
    echo "\n=== C) Staging retrieve skipped (set GANDO_DEPOSIT_ID to enable) ===\n";
}

function demo_return_url(string $query): void
{
    parse_str(ltrim($query, '?'), $params);
    $depositId = $params['depositId']
        ?? $params['deposit_id']
        ?? $params['caution_id']
        ?? null;
    $status = $params['depositStatus']
        ?? $params['deposit_status']
        ?? $params['caution_status']
        ?? null;

    if (! is_string($depositId) || ! is_string($status)) {
        echo "Invalid return URL query. Expected depositId and depositStatus.\n";
        return;
    }

    echo "  deposit_id:     {$depositId}\n";
    echo "  deposit_status: {$status}\n";

    $action = match ($status) {
        'secured' => 'Confirm booking — deposit secured.',
        'declined' => 'Release hold / notify ops — tenant declined or failed scoring.',
        'abandoned' => 'Release hold — tenant abandoned checkout.',
        default => 'Unknown status — verify with GET deposit or webhook.',
    };
    echo "  action: {$action}\n";
}

function demo_rejection_webhook(): void
{
    $secret = gando_env('GANDO_WEBHOOK_SECRET');

    $rawBody = json_encode([
        'event' => 'deposit.status_changed',
        'created_at' => gmdate('Y-m-d\TH:i:s.000\Z'),
        'data' => [
            'id' => 'dep_example',
            'reference' => 'GAN-EXAMPLE',
            'status' => 'cancelled',
            'previous_status' => 'incomplete',
            'amount_cents' => 80000,
        ],
    ], JSON_THROW_ON_ERROR);

    $signed = gando_sign_webhook($rawBody, $secret);

    try {
        WebhookVerifier::verify(
            $rawBody,
            $signed['signature'],
            $signed['timestamp'],
            $secret,
        );
    } catch (WebhookSignatureException $e) {
        fwrite(STDERR, "Webhook verification failed: {$e->getReason()}\n");
        exit(1);
    }

    $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
    $status = $payload['data']['status'] ?? 'unknown';
    $previous = $payload['data']['previous_status'] ?? 'n/a';
    $depositId = $payload['data']['id'] ?? 'n/a';

    echo "  deposit_id:      {$depositId}\n";
    echo "  status:          {$status}\n";
    echo "  previous_status: {$previous}\n";

    if (in_array($status, ['cancelled', 'payment_issue'], true)) {
        echo "  action: Mark booking as not secured; stop collection flows; notify rental operator.\n";
    } else {
        echo "  action: Handle status transition in your integration layer.\n";
    }
}

function demo_staging_retrieve(string $depositId): void
{
    try {
        $response = gando_client()->deposits->retrieve($depositId);
        $deposit = $response->object->data;

        echo "  id:     {$deposit->id}\n";
        echo "  status: {$deposit->status->value}\n";
        echo "  amount: {$deposit->amount}\n";

        if (in_array($deposit->status->value, ['cancelled', 'payment_issue', 'incomplete'], true)) {
            echo "  action: Deposit not active — treat as rejection or in-progress.\n";
        }
    } catch (Throwable $e) {
        gando_print_api_error($e);
        exit(1);
    }
}
