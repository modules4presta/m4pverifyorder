# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-09-28

### Security

- The per-customer settings were saved by a **front** controller guarded only by a shop-wide constant
  token, with the customer id taken from the request. Anyone holding that token could redirect the
  approval e-mails of any account. Saving now goes through an admin controller, behind the employee
  session and the back office token.
- That controller also interpolated the customer id into SQL after `pSQL()`, which does not neutralise
  a numeric context.
- The approval link was derived from the cart id and accepted a customer id from the URL, so a valid
  link could be used to mark a different customer verified. The token is now random, identifies the
  order by itself, works once and expires.

### Removed

- `request.php`, which called a method that does not exist and wrote to a table the installer never
  created, plus the cart button that pointed at it.
- `sendverify.php`, a second copy of the request mail.
- Three columns added to PrestaShop's `customer` table with MariaDB-only syntax and never removed.

### Changed

- Approval is tracked per order in `m4pverifyorder_approval`, not as a flag on the customer.
- The module creates its own order states instead of assuming ids 1 and 2 exist.
- Released under the MIT license, with English and Polish catalogues and the standard documentation.

### Fixed

- `Tools::displayPrice()`, removed in PrestaShop 9, was called while placing an order.
- The English mail templates were missing; two of the three existed only in Polish.
- Prices are formatted even when the hook runs without a locale in the context, as it does from the
  back office and the webservice.
