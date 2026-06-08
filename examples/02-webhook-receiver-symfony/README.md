# Webhook receiver (Symfony)

Minimal Symfony app using [`gando/partner-symfony`](https://github.com/Gando-Solutions/gando-partner-php-symfony) **v0.1.4+**: bundled `GandoWebhookController`, HMAC verification, deduplication, and typed webhook events.

## Setup

```bash
cd examples/02-webhook-receiver-symfony
composer install
cp .env.example .env
# Set GANDO_API_KEY (gando_pk_…) and GANDO_WEBHOOK_SECRET (gando_whsec_… or whsec_…)
```

## Run

```bash
symfony server:start -d --port=8788
# or: php -S 127.0.0.1:8788 -t public public/index.php
```

Webhook endpoint: `POST http://127.0.0.1:8788/webhooks/gando`

## Routing

MicroKernel apps do not auto-import bundle routes — `config/routes/gando_partner.yaml` wires the bundled controller (2 lines).

## Custom logic

See `src/EventListener/GandoWebhookDemoListener.php` — subscribe to typed events dispatched by the bundle:

| Event class | Gando `event` |
| --- | --- |
| `DepositStatusChanged` | `deposit.status_changed` |
| `DepositActivated` | `deposit.activated` |
| `DepositCaptured` | `deposit.captured` |
| `DepositExpired` | `deposit.expired` |
| `DepositCancelled` | `deposit.cancelled` |
| `RentalOperatorLinked` | `rental_operator.linked` |

Access payload fields via `$event->webhook` helpers (`depositId()`, `depositStatus()`, `previousDepositStatus()`, `amountCents()`, …). Wire format uses camelCase (`previousStatus`, `amountCents`).

## Logs

`var/log/dev.log` records inbound webhook processing from the demo listener.
