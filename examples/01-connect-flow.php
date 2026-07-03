<?php

declare(strict_types=1);

/**
 * Partner Connect — build a signed activation URL and verify link (Partner API).
 *
 * Usage:
 *   php examples/01-connect-flow.php              # print signup URL
 *   php examples/01-connect-flow.php --check    # list linked accounts
 */

require __DIR__.'/_bootstrap.php';

use Gando\Partner\Connect\UrlBuilder;

$checkOnly = in_array('--check', $argv ?? [], true);

$connectSecret = gando_env('GANDO_CONNECT_SECRET');
$partnerSlug = gando_env('GANDO_PARTNER_SLUG');
$externalId = gando_env('GANDO_EXTERNAL_ID');
$dashboardBase = gando_env('GANDO_DASHBOARD_BASE_URL', required: false);
if ($dashboardBase === '') {
    $dashboardBase = 'https://dashboard.gando.app';
}

$builder = new UrlBuilder(
    connectSecret: $connectSecret,
    partnerSlug: $partnerSlug,
    baseUrl: $dashboardBase,
);

if ($checkOnly) {
    $api = gando_client();
    $found = false;

    foreach ($api->accounts->list()->object->data as $account) {
        if ($account->externalId === $externalId) {
            echo "Linked\n";
            echo "  account_id:  {$account->accountId}\n";
            echo "  external_id: {$account->externalId}\n";
            echo "  status:      {$account->status->value}\n";
            $found = true;
            break;
        }
    }

    if (! $found) {
        echo "No linked account for external_id={$externalId}\n";
        echo "Complete signup on Gando using the URL below, then re-run with --check\n";
    }

    exit($found ? 0 : 1);
}

$signupUrl = $builder->signupUrl(
    externalId: $externalId,
    email: 'ops@example.com',
    name: 'Fleetee Ops',
    returnUrl: 'https://partner.example/gando/callback',
);

echo "Signup URL (valid 5 minutes):\n";
echo $signupUrl."\n\n";

$loginUrl = str_replace('/register?', '/login?', $signupUrl);
echo "Login URL (same signature, existing Gando account):\n";
echo $loginUrl."\n";
