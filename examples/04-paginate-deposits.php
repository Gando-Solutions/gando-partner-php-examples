<?php

declare(strict_types=1);

/**
 * List deposits with pagination (Partner API).
 *
 * Usage: php examples/04-paginate-deposits.php
 */

require __DIR__.'/_bootstrap.php';

use Gando\Partner\Models\Operations\DepositsListRequest;

$accountId = gando_env('GANDO_ACCOUNT_ID');
$api = gando_client();

$page = 1;
$limit = 20;
$totalPrinted = 0;

try {
    do {
        $response = $api->deposits->list(new DepositsListRequest(
            accountId: $accountId,
            page: $page,
            limit: $limit,
        ));

        $data = $response->object->data;
        $numPages = $data->numPages ?? $page;

        if ($page === 1) {
            echo "Deposits for account {$accountId}\n";
            echo "  total:     {$data->total}\n";
            echo "  num_pages: {$numPages}\n";
            echo str_repeat('-', 40)."\n";
        }

        foreach ($data->items as $deposit) {
            echo "{$deposit->id}  {$deposit->status->value}  {$deposit->reference}\n";
            ++$totalPrinted;
        }

        ++$page;
    } while ($page <= $numPages);

    echo str_repeat('-', 40)."\n";
    echo "Listed {$totalPrinted} deposit(s) across ".($page - 1)." page(s).\n";
} catch (Throwable $e) {
    gando_print_api_error($e);
    exit(1);
}
