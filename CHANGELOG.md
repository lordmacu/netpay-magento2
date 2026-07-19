# Changelog

Community port of NetPay's official Magento module (built for Magento 2.4.6, ZIP-only) to
**Magento Open Source 2.4.8 / PHP 8.4**, hardened against NetPay's WooCommerce plugin as the
reference implementation.

## 1.0.7

Two cosmetic 3-D Secure parity fixes vs NetPay's WooCommerce plugin (from the strict review):
- **Card details on the order:** the charge now records the `cardPrefix` and `lastFourDigits` (from
  `paymentSource.card`) as order comments, so guest / one-time cards are identifiable without a saved
  vault token — matching the notes the WooCommerce plugin adds.
- **`insecure` status:** the redirect-return status message used `unsecure`; the actual gateway term
  is `insecure` (matching WooCommerce). The status is also lower-cased now, so the upper-case gateway
  values (`FAILED`, `REJECTED`, …) actually match their message instead of always hitting the default.

## 1.0.6

Strict 3-D Secure review against NetPay's WooCommerce plugin. The happy path (DDC/referenceId,
challenge-vs-frictionless detection, confirm contract) was already at parity; these fixes harden the
**unhappy paths**, which were the only real gaps:

- **Terminal declines:** the card charge now surfaces a friendly failure and cancels the orphaned
  order when the gateway declines (`failed`/`rejected`/`insecure`), instead of returning an
  unparseable `false` that left the order stuck in `pending` with no message.
- **Frictionless review:** a `review` charge is passed through to the frontend on status alone; it no
  longer requires a `returnUrl` (which a frictionless review may not carry) — that gate silently
  broke the frictionless-3DS confirm the JS was built to handle.
- **Failed confirm:** a challenge confirm that throws (e.g. HTTP 409) now cancels the still-pending
  order, matching the frictionless branch.
- **Redirect-return (`Reside`):** treats `CHARGEABLE` as a valid paid state (it was falling through
  to *cancel*); generates the invoice on the `WAIT_THREEDS → confirm → success` path (previously only
  `DONE` did); and, on a confirm exception, re-reads the gateway state and settles if the order is
  already paid instead of cancelling a valid order (repeat-confirm 409 safety).
- **Frontend:** fixes `callbackProceed` on a failed/abandoned 3DS challenge (it left the loader
  spinning forever and passed the raw response to `errorProcessor`) and handles a terminal-decline
  (`status=failed`) charge response, in both the main and place-order charge flows.

> Note: these unhappy-path fixes can't be verified end-to-end while NetPay's sandbox Decision Manager
> declines every transaction; `php -l`, phpstan and phpcs are clean.

## 1.0.5

- **Anti-fraud:** the card charge now sends the shopper's real browser **User-Agent**, matching
  NetPay's WooCommerce plugin (which forwards `$_SERVER['HTTP_USER_AGENT']`). Previously every charge
  went out with the SDK default `Swagger-Codegen/1.0.0/php`. `getCharges` reads the browser UA
  (Magento HTTP Header) and sets it on the SDK `Configuration` singleton before the charge, so
  `OrdersApi` emits it as the request `User-Agent` — no vendored-SDK edit needed. Scoped to the
  actual charge (save-card / delete-card / 3DS-confirm return earlier).
- **Webhook (no change, by design):** audited the webhook against the WooCommerce reference. The
  WooCommerce plugin does **no** webhook authentication at all (open endpoint, no IP allowlist, no
  signature, trusts the payload). This module already exceeds that: it re-verifies every webhook
  server-to-server against the NetPay gateway (never trusting the payload status) and checks the
  amount, plus an IP allowlist. Gateway re-verification is the real anti-forgery control, so no IP
  hardening was added (a source-IP check is not the security boundary).

## 1.0.4

- **Anti-fraud:** the checkout now forwards the ThreatMetrix device fingerprint on the card charge,
  matching NetPay's WooCommerce plugin. On load it generates a session id and fires the online-metrix
  tags (a `<script>` plus a hidden iframe, `org_id` `45ssiuz3` test / `9ozphlqx` prod), then sends
  that id as both `deviceFingerPrint` and `sessionId`. Wired through all SDK layers (getCharges +
  mapping ×2 + the `Charges` model so the fields are not dropped on serialization), the same way the
  client IP (`zoneAware`, 1.0.1) was. Also fixes pre-existing broken fingerprint code in the
  save-card flow (it read a non-existent `#iframeTM` element via a bogus regex).

## 1.0.3

Aligned two address/vault details with NetPay's WooCommerce plugin (the reference):
- **Region:** the billing/shipping state is now sent as the state **code** (not the name), matching
  what the WooCommerce plugin sends (`billing_state`). The code was already computed and discarded.
- **preAuth:** no longer sent when saving a card — the WooCommerce plugin does not send it, so NetPay
  applies its default. Removes a buggy always-false ternary and an inconsistent hard-coded `true`.

## 1.0.2

- **Refunds:** admin online credit memos now refund through NetPay
  (`POST /v3/transactions/{id}/refund`). NetPay's endpoint refunds the full transaction (no
  amount), so **full refunds only** — partial refunds are rejected. Scoped to the order's store
  (multi-store).

## 1.0.1

- **Anti-fraud:** the card charge now sends the shopper's client IP as
  `zoneAware.clientIPAdress` (a signal NetPay's Decision Manager consumes), matching the WooCommerce
  plugin. Wired through all three SDK layers (getCharges + mapping + the `Charges` model so the field
  is not dropped on serialization).
- **Static analysis:** adopted **phpstan level 4** with a baseline (`phpstan.neon` +
  `phpstan-baseline.neon`); phpstan reports no errors and new code is held to level 4.

## 1.0.0

### Compatibility (2.4.8 / PHP 8.4)
- Self-contained packaging: the NetPay PHP SDK is vendored under `Sdk/` and its PSR-4 namespaces
  (`Netpay\Client\`, `BusinessLayer\Netpay\`) are registered from `registration.php`. No separate
  `netpay/custom` Composer package required. Guzzle is not vendored (Magento ships it).
- PHP 8.4: fixed 47 implicit-nullable parameters (`Type $x = null` → `?Type $x = null`) across the
  module and vendored SDK; fixed remaining PHPDoc/type issues surfaced by phpstan.
- Migrated the obsolete `Setup/InstallSchema` to declarative `etc/db_schema.xml` (+ whitelist).
- Added a module `composer.json` (installable via Composer or `app/code` copy).

### 3-D Secure flow (aligned with NetPay's WooCommerce plugin)
- Backend `confirm3DS` no longer confirms blindly: on the frictionless branch it reads the real
  transaction state (`GET /v3/transactions/{id}`) and branches `WAIT_THREEDS` → confirm,
  `DONE`/`CHARGEABLE` → success, `FAILED`/`REJECTED`/`REJECT` → surface the reason. Frictionless
  confirm sends the literal `processorTransactionId=null`.
- Frontend decides a real 3DS challenge by fields (`acsUrl && paReq && authenticationTransactionID`)
  and only redirects to success when the confirm status is `success`.
- `friendlyResponse()`: maps ~60 raw gateway responses to friendly Spanish messages.
- Orphaned orders are cancelled on a `FAILED`/`REJECTED` 3DS confirm.
- Device-data-collection (Cardinal ReferenceId) gating: the checkout blocks the charge until the
  ReferenceId is ready, so cards enrol properly.
- Retry-safety: sticky Cardinal/Songbird 3DS storage is cleared at the start of each attempt.
- Card webhook reconciliation: the webhook re-verifies the transaction against the gateway and
  settles (`DONE`/`CHARGEABLE`) or cancels (`FAILED`/`REJECT`), idempotently.

### Multi-store
- All config (public/secret keys, mode, gateway host) is scoped to the order's store, including the
  webhook (which carries no store context) and the charge.
- Saved-card NetPay client id is stored per store (`netpay_customer.store_id`, unique on
  `customer_id + store_id`), so cards never leak across stores/NetPay accounts. Works whether stores
  share one account or use different ones.

### Bug fixes (highlights, ~40 across 6 audit passes, ~6 fatal)
- Fatal: `Reside` called `getInfoMessageByStatus()` as a global function.
- Fatal: `getCharges` cash branch used `$ex->getMessage()` outside its catch (Error on null).
- Fatal: virtual/downloadable-only order crashed on `getShippingAddress()->getData()` (null).
- Fatal: webhook non-POST branch used an undeclared `$this->resultJsonFactory`.
- `getClientId` could return null and break the caller's `list()` destructuring.
- `saveSecondCard` set the vault token on the wrong payment object.
- Guest orders with `saveCc`/`cardSelected` crashed on `customerRepository->getById(null)`.
- `di.xml` mapped `CustomerLinkInterface` to a ResourceModel instead of the entity.
- Idempotent invoicing; guarded `savecc` order load; validated `Savecard` params.
- My-Account "Add Card" used a hardcoded sandbox key + forced sandbox + `http://` CDN → broken in
  live/HTTPS; now uses the real key/mode and https. Dynamic expiry-year list.
- Fixed frontend `errorProcessor.process(...)` crashes and an undeclared `self` global.

### Security / quality
- Admin config AJAX controllers scoped to the `Magento_Config::config` ACL.
- Escaped all template output (fixed 39 unescaped-output findings); removed debug leftovers.
- `phpcs --standard=Magento2` (severity 10, excluding the vendored `Sdk/`) is clean.

### Known limitations / roadmap
- The payment method still extends the deprecated `Magento\Payment\Model\Method\AbstractMethod`
  (charging is done out-of-band via the webapi controller); migration to the Payment Provider
  Gateway is planned.
- Refunds are **full only** (NetPay's refund endpoint takes no amount); partial refunds are not
  supported. Capture is online at charge time.
