# Netpay_Payment — NetPay for Magento 2 (2.4.8 / PHP 8.4)

Community port of NetPay's official Magento module (which shipped only as a ZIP built for
Magento 2.4.6) to **Magento Open Source 2.4.8** running on **PHP 8.4**. It provides card payments
with 3-D Secure (Cardinal/Songbird), card vaulting (saved cards) and OXXO Pay (cash).

The module is **self-contained**: NetPay's PHP SDK is vendored under `Sdk/` and autoloaded by
`registration.php`, so there is no separate `netpay/custom` Composer dependency to install. Guzzle
is not vendored — Magento already ships and autoloads it.

## Compatibility

| | |
|---|---|
| Magento Open Source | 2.4.8 (tested on 2.4.8-p5) |
| PHP | 8.1 – 8.4 |
| HTTP client | `guzzlehttp/guzzle ^7.5` (provided by Magento) |

## Install

### Composer (recommended)

```bash
composer require netpay/module-payment
bin/magento module:enable Netpay_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual (app/code)

Copy this directory to `app/code/Netpay/Payment`, then:

```bash
bin/magento module:enable Netpay_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Stores → Configuration → Sales → Payment Methods → NetPay**

- Enable the gateway and the credit-card method.
- Set the mode (`test` / `live`) and the corresponding public/secret keys.

Config paths (if you script it with `bin/magento config:set`):

```
payment/netpay/enable            1
payment/netpay/active            1
payment/netpay/payment_mode      test
payment/netpay/public_key_test   pk_...
payment/netpay/secret_key_test   sk_...
```

## 3-D Secure flow

The checkout follows NetPay's contract (aligned with NetPay's WooCommerce plugin):

1. Tokenize the card and create the charge (`POST /v3.5/charges`).
2. If the charge returns `status: review`:
   - **Challenge** (the response carries `acsUrl` + `paReq` + `authenticationTransactionID`):
     run the Cardinal step-up, then confirm with the returned `processorTransactionId`.
   - **Frictionless** (no `acsUrl`): read the real transaction state first
     (`GET /v3/transactions/{id}`) and branch:
     - `WAIT_THREEDS` → confirm (frictionless confirm sends the literal `processorTransactionId=null`).
     - `DONE` / `CHARGEABLE` → already approved.
     - `FAILED` → surface the real reason (do **not** confirm blindly).

> **Decision Manager:** a charge can be sent to `review` and then `FAILED` by NetPay's anti-fraud
> Decision Manager (`responseCode 88`, `"Enviada a review por DM"`). That is an account/gateway-side
> decision, not a client bug — the confirm returns HTTP 409 for such transactions by design. This
> module surfaces that state instead of masking it.

## Notes / roadmap

- The payment method still extends the deprecated `Magento\Payment\Model\Method\AbstractMethod`
  (works on 2.4.8). Migrating to the Payment Provider Gateway (Command/Adapter) architecture is
  planned.
- Schema is declarative (`etc/db_schema.xml`): a `token` column on `sales_order` and the
  `netpay_customer` table (customer → NetPay client id).
- `Sdk/` is Swagger-generated SDK code and is intentionally excluded from coding-standard checks.
