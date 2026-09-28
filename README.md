# M4P Order Approval for PrestaShop 8 & 9

**Let your customers' buyers order on their own, and still give their manager the last word — one link in an e-mail releases the order.**

> **Meta description (150 chars):** Hold a PrestaShop order until the person responsible at the customer company approves it by e-mail. Per-customer setting. Free MIT module for B2B shops.

---

## Why a wholesale order needs a second pair of eyes

In a company that buys from you, the person who knows what is needed is rarely the person who
signs off on spending. Without a control in the shop, that conversation happens over the phone,
after the order was already placed:

- **Orders you can trust** — nothing goes into production until the customer's own manager agrees
- **No more cancellations** — the approval happens before you pick and pack, not after
- **Per customer** — the requirement applies only to the companies that asked for it
- **Nothing to install on their side** — the approver gets an e-mail with one button

## What the module does

You mark a customer as needing approval and give the e-mail of the person who approves. When someone
from that company places an order, the order goes into **Waiting for approval** instead of being
processed, and the approver receives an e-mail with the order, its items and a link. One click on
that link moves the order to **Approved by the customer company** and tells the buyer by e-mail.

### Key features

- **Its own order states** — created on install, so nothing depends on the numbering of your shop
- **One-time link** — the token identifies the order, works once and expires after a set number of days
- **Per-customer setting** — edited on the customer page in the back office
- **Both sides are told** — the approver gets the request, the buyer gets the confirmation
- **Nothing is added to PrestaShop's tables** — the module keeps its data in its own two tables

### What it does not do

The module does not build an approval queue with its own screen, does not support more than one
approver per customer and does not reject orders by itself. An order that is never approved simply
stays in **Waiting for approval** for you to handle.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.2.5+ |
| Requirements | a working shop e-mail configuration |
| Multistore | Settings and approvals are shared across shops |
| Themes | The approval page extends `page.tpl` (all standard themes have it) |

The module performs no core overrides. It creates two tables, `m4pverifyorder_customer` and
`m4pverifyorder_approval`, and two order states. Uninstalling drops the tables and hides the states,
which keeps the history of orders that used them.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open **Customers**, pick a customer and find the **Order approval** panel.
3. Tick the box, enter the approver's e-mail and save.
4. Place a test order as that customer and check that it lands in **Waiting for approval**.

## Configuration options

| Setting | Where | Description |
|---|---|---|
| **Days the approval link stays valid** | Module configuration | How long the link from the e-mail works. Default 7 days. |
| **Orders from this customer need approval** | Customer page | Turns the requirement on for that customer. |
| **E-mail of the person approving them** | Customer page | Where the approval request is sent. |

## Frequently asked questions

**What happens when the link expires?**
It stops working and shows a message. The order stays in **Waiting for approval** and you move it
forward in the back office yourself.

**Can the same link be used twice?**
No. Once an order is approved the link stops working, so a forwarded e-mail cannot approve anything
a second time.

**Can a customer have more than one approver?**
No, one address per customer. Use a shared mailbox or a distribution list if several people should
see the request.

**Does the approver need an account in my shop?**
No. The link works without signing in — that is what makes it usable for someone who never visits
the shop.

**What happens to orders waiting for approval when I uninstall the module?**
The tables are dropped and the two order states are hidden. Orders keep the state they were in, and
you carry on in the back office.

---

**Keywords:** PrestaShop order approval, B2B order workflow, purchase approval, manager approval,
wholesale ordering, order on hold.

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/produkty/sklep-b2b-prestashop) — we build B2B stores on PrestaShop.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
