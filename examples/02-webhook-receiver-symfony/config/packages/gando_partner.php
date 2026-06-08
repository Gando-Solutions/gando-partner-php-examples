<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * PHP config reads env vars after Symfony Runtime loads .env.
 * The bundle validates key prefixes at compile time, so %env(...)% placeholders in YAML fail validation.
 */
return static function (ContainerConfigurator $container): void {
    $container->extension('gando_partner', [
        'api_key' => (string) ($_ENV['GANDO_API_KEY'] ?? getenv('GANDO_API_KEY')),
        'base_url' => 'https://staging.gando.app',
        'webhooks' => [
            'secret' => (string) ($_ENV['GANDO_WEBHOOK_SECRET'] ?? getenv('GANDO_WEBHOOK_SECRET')),
            'tolerance_seconds' => 300,
            'path' => '/webhooks/gando',
            'dedup_ttl_seconds' => 86400,
        ],
    ]);
};
