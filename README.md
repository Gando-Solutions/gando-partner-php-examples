# gando-partner-php-examples

Runnable examples for the [Gando Partner PHP SDK](https://github.com/Gando-Solutions/gando-partner-php) (`gando/partner`). These scripts are kept in a separate repository so the SDK package stays lean.

**Requirements:** PHP 8.2+, Composer.

## Quickstart

```bash
composer install
cp .env.example .env
# Edit .env with your staging partner credentials
php examples/01-create-deposit.php
```

If `gando/partner` is not on Packagist yet, add a VCS repository before `composer install`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/Gando-Solutions/gando-partner-php" }
  ]
}
```

## Environment variables

| Variable | Description |
| --- | --- |
| `GANDO_API_KEY` | Partner API key (`gando_pk_…`) |
| `GANDO_CONNECT_SECRET` | Connect signing secret (`gando_cs_…`) |
| `GANDO_WEBHOOK_SECRET` | Webhook HMAC secret (`gando_whsec_…`) |
| `GANDO_BASE_URL` | API base URL (default: `https://staging.gando.app`) |
| `GANDO_ACCOUNT_ID` | Linked rental operator account ID |
| `GANDO_PARTNER_SLUG` | Partner slug for Connect (Symfony example) |
| `GANDO_DEPOSIT_ID` | Optional deposit ID for `03-handle-rejection.php` |

### Obtaining `GANDO_ACCOUNT_ID`

List linked rental operators with the SDK or API:

```bash
php -r "require 'vendor/autoload.php'; /* use accounts->list() */"
```

Or call `GET /api/partner/accounts` with your partner key. Use the `account_id` from a linked operator.

## Staging vs production

| Environment | `GANDO_BASE_URL` |
| --- | --- |
| Staging | `https://staging.gando.app` |
| Production | `https://gando.app` |

## Examples

| Example | Command | Purpose |
| --- | --- | --- |
| Create deposit | `php examples/01-create-deposit.php` | Create a deposit with inline redirect |
| Webhook (plain PHP) | `php examples/02-webhook-receiver-plain.php` | CLI signature self-test; or HTTP server below |
| Webhook (Symfony) | See [examples/02-webhook-receiver-symfony/README.md](examples/02-webhook-receiver-symfony/README.md) | Bundle controller + event subscribers |
| Handle rejection | `php examples/03-handle-rejection.php` | Return URL + webhook rejection handling |
| Paginate deposits | `php examples/04-paginate-deposits.php` | List deposits page by page |

### Local webhook testing (plain PHP)

```bash
php -S 127.0.0.1:8787 examples/02-webhook-receiver-plain.php
```

Expose with ngrok, register the URL via `POST /api/partner/webhooks`, then trigger a test delivery with `POST /api/partner/webhooks/{id}/test`.

CLI self-test (no HTTP):

```bash
php examples/02-webhook-receiver-plain.php
```

## Links

- [Partner PHP SDK](https://github.com/Gando-Solutions/gando-partner-php)
- [Partner Symfony bundle](https://github.com/Gando-Solutions/gando-partner-php-symfony)
- [Partner API docs](https://developers.gando.app/partner)

## License

MIT — see [LICENSE](LICENSE).
