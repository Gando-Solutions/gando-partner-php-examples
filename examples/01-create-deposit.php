<?php

declare(strict_types=1);

/**
 * Create a deposit for a linked rental operator (Partner API).
 *
 * Usage: php examples/01-create-deposit.php
 */

require __DIR__.'/_bootstrap.php';

use Gando\Partner\Models\Operations\PartnerCreateDepositBody;

$accountId = gando_env('GANDO_ACCOUNT_ID');
$api = gando_client();

$body = new PartnerCreateDepositBody(
    accountId: $accountId,
    amount: 800.0,
    rentalContract: 'CTR-'.date('Y').'-'.random_int(100, 999),
    contractStartAt: gmdate('Y-m-d\TH:i:s.000\Z'),
    contractEndAt: gmdate('Y-m-d\TH:i:s.000\Z', strtotime('+7 days')),
    clientId: null,
    depositUrlGeneration: true,
    returnUrl: 'https://partner.example/checkout/complete',
);

try {
    $response = $api->deposits->create($body);
    $deposit = $response->object->data;

    echo "Deposit created\n";
    echo "  id:          {$deposit->id}\n";
    echo "  reference:   {$deposit->reference}\n";
    echo "  status:      {$deposit->status->value}\n";
    if ($deposit->depositUrl !== null && $deposit->depositUrl !== '') {
        echo "  deposit_url: {$deposit->depositUrl}\n";
    }
} catch (Throwable $e) {
    gando_print_api_error($e);
    exit(1);
}
