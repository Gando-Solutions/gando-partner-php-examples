<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Gando\Partner\Api\Client;

require dirname(__DIR__).'/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

function gando_env(string $name, bool $required = true): string
{
    $value = $_ENV[$name] ?? getenv($name);

    if (! is_string($value) || $value === '') {
        if ($required) {
            fwrite(STDERR, "Missing required environment variable: {$name}\n");
            exit(1);
        }

        return '';
    }

    return $value;
}

function gando_base_url(): string
{
    $url = $_ENV['GANDO_BASE_URL'] ?? getenv('GANDO_BASE_URL');

    if (is_string($url) && $url !== '') {
        return rtrim($url, '/');
    }

    return 'https://staging.gando.app';
}

function gando_client(): Client
{
    return new Client(
        apiKey: gando_env('GANDO_API_KEY'),
        baseUrl: gando_base_url(),
    );
}

/**
 * @return array{signature: string, timestamp: string}
 */
function gando_sign_webhook(string $rawBody, string $secret): array
{
    $timestamp = (string) time();
    $signedPayload = $timestamp.'.'.$rawBody;
    $signature = 'sha256='.hash_hmac('sha256', $signedPayload, $secret);

    return ['signature' => $signature, 'timestamp' => $timestamp];
}

function gando_print_api_error(Throwable $e): void
{
    if ($e instanceof \Gando\Partner\Models\Errors\ErrorEnvelopeThrowable) {
        $err = $e->container->error;
        fprintf(
            STDERR,
            "[%s] %s (requestId: %s)\n",
            $err->code->value,
            $err->message,
            $err->requestId ?? 'n/a',
        );

        return;
    }

    if ($e instanceof \Gando\Partner\Models\Errors\APIException) {
        fprintf(STDERR, "HTTP %d: %s\n", $e->statusCode, $e->body);

        return;
    }

    fprintf(STDERR, "%s\n", $e->getMessage());
}
