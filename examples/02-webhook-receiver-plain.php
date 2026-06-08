<?php

declare(strict_types=1);

/**
 * Gando partner webhook receiver (plain PHP).
 *
 * CLI:  php examples/02-webhook-receiver-plain.php
 * HTTP: php -S 127.0.0.1:8787 examples/02-webhook-receiver-plain.php
 */

require __DIR__.'/_bootstrap.php';

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;

if (PHP_SAPI === 'cli') {
    run_cli_self_test();
    exit(0);
}

run_http_receiver();

function run_cli_self_test(): void
{
    $secret = gando_env('GANDO_WEBHOOK_SECRET');

    $rawBody = json_encode([
        'event' => 'deposit.status_changed',
        'created_at' => gmdate('Y-m-d\TH:i:s.000\Z'),
        'data' => [
            'id' => 'dep_example',
            'reference' => 'GAN-EXAMPLE',
            'status' => 'active',
            'previous_status' => 'pending',
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
        fwrite(STDERR, "Verification failed: {$e->getReason()}\n");
        exit(1);
    }

    echo "Webhook signature verification OK (CLI self-test)\n";
    echo "Start HTTP receiver: php -S 127.0.0.1:8787 examples/02-webhook-receiver-plain.php\n";
}

function run_http_receiver(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Method Not Allowed';
        exit;
    }

    $secret = $_ENV['GANDO_WEBHOOK_SECRET'] ?? getenv('GANDO_WEBHOOK_SECRET');
    if (! is_string($secret) || $secret === '') {
        http_response_code(500);
        echo 'Missing GANDO_WEBHOOK_SECRET';
        exit;
    }

    $rawBody = file_get_contents('php://input') ?: '';
    $signature = $_SERVER['HTTP_X_GANDO_SIGNATURE'] ?? '';
    $timestamp = $_SERVER['HTTP_X_GANDO_TIMESTAMP'] ?? '';
    $event = $_SERVER['HTTP_X_GANDO_EVENT'] ?? '';

    try {
        WebhookVerifier::verify($rawBody, $signature, $timestamp, $secret);
    } catch (WebhookSignatureException) {
        http_response_code(400);
        exit;
    }

    $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);

    error_log(sprintf(
        '[gando-webhook] event=%s deposit_id=%s',
        $event !== '' ? $event : ($payload['event'] ?? 'unknown'),
        is_array($payload['data'] ?? null) ? ($payload['data']['id'] ?? 'n/a') : 'n/a',
    ));

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['received' => true]);
}
