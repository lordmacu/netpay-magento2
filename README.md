# NetPay for Magento 2 (`Netpay_Payment`)

![Magento](https://img.shields.io/badge/Magento-2.4.8-orange)
![PHP](https://img.shields.io/badge/PHP-8.1%E2%80%938.4-777bb4)
![3-D Secure](https://img.shields.io/badge/3--D%20Secure-Cardinal-blue)
![Multi-store](https://img.shields.io/badge/multi--store-yes-success)

Community port of NetPay's official Magento module (which shipped only as a ZIP built for
**Magento 2.4.6**) to **Magento Open Source 2.4.8** on **PHP 8.4**, hardened against NetPay's
WooCommerce plugin as the reference implementation.

It adds NetPay card payments with 3-D Secure, saved cards (vault) and OXXO Pay (cash), and is
**self-contained** — NetPay's PHP SDK is vendored under `Sdk/` and autoloaded by `registration.php`,
so there is no separate `netpay/custom` Composer dependency. Guzzle is not vendored (Magento ships it).

---

## Features

- **Card payments** with **3-D Secure** (Cardinal/Songbird), frictionless + challenge flows.
- **OXXO Pay** (cash) with an OXXO-Pay checkout method and a reference/receipt block.
- **Saved cards (vault)** — save at checkout, manage in *My Account*, delete; **client id scoped per
  store** so cards never leak across stores / NetPay accounts.
- **Webhook receiver** — OXXO settlement + generic card reconciliation (re-verifies the transaction
  against the gateway; idempotent).
- **Multi-store aware** — every config read (keys / mode / gateway host) is scoped to the order's
  store, including the webhook (which carries no store context) and the charge.
- **Friendly error messages** — ~60 raw gateway responses mapped to friendly Spanish messages.
- **Retry-safe 3-D Secure** — sticky Cardinal/Songbird state is cleared between attempts.
- **Self-contained** SDK, **declarative schema**, `phpcs --standard=Magento2` clean (excluding `Sdk/`).

## Compatibility

| | |
|---|---|
| Magento Open Source | 2.4.8 (tested on 2.4.8-p5) |
| PHP | 8.1 – 8.4 |
| HTTP client | `guzzlehttp/guzzle ^7.5` (provided by Magento) |
| 3-D Secure | Cardinal Commerce (Songbird), sandbox + live |

## Install

### Composer

```bash
composer require netpay/module-payment
bin/magento module:enable Netpay_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual (app/code)

Copy this repository to `app/code/Netpay/Payment`, then:

```bash
bin/magento module:enable Netpay_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Stores → Configuration → Sales → Payment Methods → NetPay**

- Enable the gateway and the credit-card method.
- Set the mode (`test` / `live`) and the matching public/secret keys.
- (OXXO) Enable OXXO Pay and register the webhook from the config page button.

All fields are **store-scoped** (`showInStore=1`), so each store view can have its own NetPay account.

Config paths (if you script it with `bin/magento config:set`):

```
payment/netpay/enable            1
payment/netpay/active            1     # credit-card method
payment/netpay/payment_mode      test  # or live
payment/netpay/public_key_test   pk_...
payment/netpay/secret_key_test   sk_...
payment/netpay/public_key_live   pk_...
payment/netpay/secret_key_live   sk_...
```

> The **secret key** is only ever used server-side (to build the SDK `PaymentManager`); it is never
> exposed to the browser. The checkout config only carries the public key.

## 3-D Secure flow

The card flow follows NetPay's contract (aligned with NetPay's WooCommerce plugin):

1. Tokenize the card client-side (`NetPay.token.create`) — the PAN never reaches the server.
2. Device Data Collection: the checkout generates a Cardinal ReferenceId and **blocks the charge
   until it is ready**, then sends it on the charge.
3. `POST /v3.5/charges`. If the charge returns `status: review`:
   - **Challenge** (response carries `acsUrl` + `paReq` + `authenticationTransactionID`): run the
     Cardinal step-up, then confirm with the returned `processorTransactionId`.
   - **Frictionless** (no `acsUrl`): read the real state first (`GET /v3/transactions/{id}`) and branch:
     - `WAIT_THREEDS` → confirm (frictionless confirm sends the literal `processorTransactionId=null`).
     - `DONE` / `CHARGEABLE` → already approved.
     - `FAILED` / `REJECTED` / `REJECT` → surface a friendly reason and cancel the pending order.

> **Decision Manager:** a charge can be sent to `review` and then `FAILED` by NetPay's anti-fraud
> Decision Manager (`responseCode 88`, "Enviada a review por DM"). That is an account/gateway-side
> decision, not a client bug. This module surfaces that state instead of masking it (the raw
> `confirm` returns HTTP 409 for such transactions).

## Saved cards (vault)

- Cards are saved via NetPay's client/vault API and shown in *My Account → My Credit Cards* and in
  the checkout saved-card selector.
- The link between a Magento customer and their NetPay client id lives in `netpay_customer`
  (`customer_id`, **`store_id`**, `netpay_id`), unique on `(customer_id, store_id)`. This makes the
  vault correct in multi-store setups whether stores share one NetPay account or use different ones.

## Webhook

Register the webhook URL from the admin config (OXXO section). The receiver
(`Controller/Payment/ApiController`, route `netpay/payment/apicontroller`):

- Validates the source IP against NetPay's ranges.
- Matches the order by token and amount.
- **OXXO** (`oxxopay.paid`): verifies with NetPay and invoices the order.
- **Card** (any other event): re-verifies the transaction against `GET /v3/transactions` and settles
  (`DONE`/`CHARGEABLE` → invoice) or cancels (`FAILED`/`REJECT`), idempotently.
- Config (keys/host) is scoped to the **order's** store, not the default store.

## Architecture

```
Netpay/Payment/
├── registration.php          # module + self-contained SDK PSR-4 autoloader
├── composer.json             # magento2-module
├── etc/                      # di, system.xml, config.xml, webapi, db_schema, routes
├── Api/  Model/  Block/  Controller/  Helper/  Setup/  Logger/  view/
└── Sdk/                      # vendored NetPay PHP SDK (Swagger)
    ├── lib/                  # Netpay\Client\  (API + models)
    ├── businessLayer/        # BusinessLayer\Netpay\  (features, mapping)
    └── config/               # SDK .ini config (hosts, feature map)
```

- The payment charge runs out-of-band via the `POST /V1/payment/charges` webapi
  (`ChargesApiManagement`), not through Magento's payment gateway commands.
- Schema is declarative (`etc/db_schema.xml`): a `token` column on `sales_order` and the
  `netpay_customer` table.

## Development

```bash
# Coding standard (excluding the vendored Swagger SDK)
vendor/bin/phpcs --standard=Magento2 --severity=10 --ignore="*/Sdk/*" app/code/Netpay/Payment
```

## Known limitations / roadmap

- Extends the deprecated `Magento\Payment\Model\Method\AbstractMethod` (works on 2.4.8; charging is
  out-of-band). Migration to the Payment Provider Gateway is planned.
- No admin-side refund/capture yet (the SDK supports it; not wired into Magento).
- Extra anti-fraud signals the WooCommerce plugin sends (client IP `zoneAware`, ThreatMetrix device
  fingerprint) are not yet forwarded on the charge.

See [CHANGELOG.md](CHANGELOG.md) for the full list of fixes (~40 across 6 audit passes, ~6 fatal).

## License / provenance

Licensed under **PolyForm Noncommercial 1.0.0** — see [LICENSE](LICENSE). In short:

- **Free for noncommercial use**, with **attribution** (keep the notice) and please **let the author
  know** you're using it (open an issue or contact [@lordmacu](https://github.com/lordmacu)).
- **Commercial / for-profit use requires a separate paid commercial license** — contact
  [@lordmacu](https://github.com/lordmacu).

> **Provenance:** this is a community **port** of NetPay's official Magento module and it **bundles
> NetPay's PHP SDK** (`Sdk/`). Those underlying components are NetPay's proprietary property and are
> subject to NetPay's own terms. The license above is granted over the **port contributions** only —
> it does not grant rights to NetPay's software; obtain the appropriate authorization from NetPay for
> your use case. (Not legal advice.)
