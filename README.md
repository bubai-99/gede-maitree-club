# Gede Maitree Club

The web application for Gede Maitree Club (গেদে মৈত্রী ক্লাব), a neighbourhood club in
Kolkata. One Laravel codebase serves four surfaces:

- **Website** (`/`): public, bilingual (English and Bengali), SEO friendly.
- **Member app** (`/app`): families see dues, receipts, their ID card, events and notices.
- **Committee console** (`/admin`): members, fees, events and the website CMS.
- **Gate scanner** (`/admin/scan`): planned for a later milestone.

The build plan, roles and data model are in [PLAN.md](PLAN.md). The clickable prototype in the
design repository (`Design/mock`) is the reference for every screen, word and colour.

## Stack

Laravel 13 on PHP 8.4, PostgreSQL 17, Blade with Alpine.js and Tailwind CSS 4 (built with Vite),
`spatie/laravel-permission` for roles, `spatie/laravel-medialibrary` for uploads,
`spatie/laravel-activitylog` for the audit trail, `bacon/bacon-qr-code` for QR codes, Laravel Pint,
Larastan and PHPUnit.

## Requirements

- PHP 8.4 with the `pdo_pgsql`, `intl`, `mbstring`, `gd`, `exif`, `zip` and `fileinfo` extensions
- Composer 2
- Node.js 20 and npm
- PostgreSQL 17

## Setup

```bash
composer install
npm install
cp .env.example .env          # then fill in DB_PASSWORD and anything else you need
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer run dev              # app, queue, logs and Vite together
```

Create the two databases first (names are only a suggestion):

```sql
CREATE ROLE gmc LOGIN PASSWORD '...' CREATEDB;
CREATE DATABASE gede_maitree OWNER gmc;
CREATE DATABASE gede_maitree_testing OWNER gmc;
```

## Tests and checks

Tests run against the PostgreSQL database `gede_maitree_testing` (see `phpunit.xml`). Put the
test database credentials in a `.env.testing` file (it is git-ignored), for example:

```dotenv
APP_ENV=testing
APP_KEY=base64:...            # any key, e.g. copy the one from .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gede_maitree_testing
DB_USERNAME=gmc
DB_PASSWORD=...
```

```bash
php artisan test               # feature and unit tests
vendor/bin/pint --test         # code style
vendor/bin/phpstan analyse     # static analysis (Larastan)
npm run build                  # production assets
```

GitHub Actions (`.github/workflows/ci.yml`) runs all four on every push and pull request, with a
`postgres:17` service.

## Sample data

`php artisan migrate --seed` loads the three account books, the fee plans and SAMPLE content
copied from the prototype (`database/seeders/data/prototype.json`): events, nursery plants,
serving packages, the committee list, notices and 24 sample families, plus SAMPLE money
(`SampleAccountsSeeder`): this year's receipt register up to MC-26-0412 with its khata lines, monthly
expenses and bank deposits, the catering and nursery books, one Razorpay payment and one UPI payment
waiting for the treasurer (every khata note says "Sample"). All names, phone numbers,
amounts and photos are placeholders, never real member data. While the `sample_data` site
setting is on, the website footer says that the content is a sample.

Receipt numbers (`MC-26-0142`, `CT-26-0018`, `NS-26-0311`) come from `App\Support\ReceiptNumbers`,
which locks the counter row for the book and year inside a transaction.

## Signing in

| Who | Where | With |
|---|---|---|
| Member families | `/app/login` | mobile number or email + password, or a one-time code |
| Committee | `/admin/login` | mobile number or email + password |

Both use Laravel's own authentication (one `users` table, the `web` guard). A member account
belongs to a family (`users.family_id`); a committee account has `is_committee` and at least one
committee role. The `member` and `committee` middleware keep the two apart: members get a 403 in
the console and committee-only accounts are sent from the member app to the console. Someone who
is both can use both.

- Five wrong passwords in a minute (per login and address) throttle the form; ten wrong passwords
  for one login lock it for 15 minutes. Sessions are regenerated on sign-in and invalidated on
  sign-out; every form has a CSRF token.
- Forgotten passwords: `/forgot-password` emails a reset link. The mail driver is `log` until the
  club has an email account, so the link is written to `storage/logs/laravel.log`.
- One-time codes: `App\Contracts\OtpSender` with a log-only driver (`OTP_DRIVER=log`) that writes
  the code to the log. Real SMS in India needs a DLT-registered sender and template; add a driver
  for the chosen SMS provider when the club has one.

### Roles

Roles from the plan are seeded by `RoleSeeder` (spatie/laravel-permission): Admin (Secretary),
Treasurer, Puja in-charge, Catering in-charge, Nursery in-charge, Volunteer, Website editor and
Member. Permissions are listed in `App\Enums\Role::permissions()`; the Admin passes every check
(`Gate::before`). Policies in `app/Policies` guard every model the console touches.

### Sample sign-ins

`php artisan migrate --seed` creates two SAMPLE accounts:

- a committee admin, email `SEED_ADMIN_EMAIL` (default `admin@example.org`)
- a member of the sample family MC-0104, email `SEED_MEMBER_EMAIL` (default `member@example.org`)

Their passwords come from `SEED_ADMIN_PASSWORD` and `SEED_MEMBER_PASSWORD` in `.env`. If those are
blank the seeder makes random passwords and prints them once in the console. These are sample
accounts for trying the app; never put real people's details here.

## Website

Every prototype screen is a real route: `/` (home), `/events` and `/events/{slug}`, `/notices` and
`/notices/{slug}`, `/catering`, `/nursery`, `/gallery`, `/chanda`, `/about`, `/contact`, `/join`,
`/volunteer`, `/panjika`, `/privacy`, `/terms`, plus a custom 404, `/sitemap.xml` and `/robots.txt`.
Lists show published content only. Page text that the committee can edit is listed in
`config/pages.php`; titles and descriptions for search engines are in `config/seo.php` (both can be
changed in the console). The forms (contact, membership, catering enquiry, volunteer, photos, chanda
pledge) are validated by FormRequests, carry a hidden honeypot field, are limited to 5 sends a
minute per address and are saved to `enquiries` for the committee.

- **Panjika:** a placeholder page for now. The prototype computes tithis in the browser; the club
  must check every date against its panjika before a real calendar goes online.
- **Photos:** sample photos are hotlinked from Unsplash with the photographer credited, as Unsplash
  asks. Swap them for the club's own (upload in the console, or edit `config/photos.php`).

## Committee console and website CMS

`/admin` has a sidebar on laptops and a bottom bar on phones. **Today** shows live counts (families,
dues, money collected this month, open enquiries, coming events, plants running low). **Website**
edits the public site without code:

- events, notices, nursery plants, serving packages and the committee list, each with
  *Save as draft* and *Save and publish* (the website only ever shows published content);
- page banners and text for every page, in English and Bengali (`config/pages.php`), kept as a
  draft until published;
- club contact details, the top menu (show, hide, rename, reorder) and the search-engine title and
  description of each page;
- enquiries from the website forms, marked as handled;
- the audit log (Admin only): every change, with who made it (spatie/laravel-activitylog).

Photos are uploaded with spatie/laravel-medialibrary to the `public` disk (JPG, PNG or WebP, up to
4 MB). Run `php artisan storage:link` once so they are served. Every committee role may look at the
website screens; only the Admin and the Website editor may change or publish anything (policies in
`app/Policies`, checked on every route).

## Member app

`/app` (sidebar on laptops, tab bar on phones): home with the family's dues, the next event and the
latest notices; paying dues online (see *Fees, payments and the khata*); the statement; receipts with
a printable receipt page; a member card for each
person in the family with a gate QR on the back; events; notices (including members-only ones); and
the family profile. Members only ever see their own family's records (`FamilyPolicy`,
`PaymentPolicy`).

QR codes are drawn on the server as SVG with `bacon/bacon-qr-code` 3.x. (`simplesoftwareio/simple-qrcode`
was tried first, but it pins bacon 2.x, which raises deprecation warnings on PHP 8.4.) A card's QR
holds the member number and an HMAC signature (`App\Support\QrCode`), so the future offline gate
scanner can check it without a network.

### Installing (PWA)

Two web app manifests, `public/member.webmanifest` (member app and member sign-in) and
`public/admin.webmanifest` (console and committee sign-in), each with its own icons in
`public/icons`. `public/sw.js` serves pages network first with a cache fallback, so a page already
opened still loads on a weak network; it is registered by the member and console layouts, and its
cache is cleared when someone signs out.

## Fees, payments and the khata

Money is kept in whole rupees everywhere; paise appear only at the Razorpay boundary (order amounts,
and Razorpay's fee as it reports it). Every change to a payment goes through
`App\Payments\PaymentService`, inside a database transaction with the payment row locked, so the
checkout callback, a webhook and the treasurer can all confirm the same payment and only the first one
issues a receipt number (`App\Support\ReceiptNumbers`, Club book series `MC-26-0001`), marks the months
paid, writes the khata and the audit log.

### Member app: Pay dues (`/app/pay`)

The family's unpaid months are listed and all ticked. Only due ids go to the server; the amount is
added up on the server from the family's own months, never taken from the browser. Then, depending on
configuration (`App\Payments\PaymentGateway`):

- **Razorpay** (when `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` are set): a pending payment and a
  Razorpay order are created (amount in paise, currency INR, `receipt` = our reference `GMC-P0000123`,
  notes with the family number and payment id). Standard Checkout (`checkout.js`) opens from an Alpine
  component with the public key, the order id, the member's name and phone, and the club colour. Its
  handler posts `razorpay_payment_id`, `razorpay_order_id` and `razorpay_signature`; the server checks
  the HMAC-SHA256 of `order_id|payment_id` with the key secret (against the order id it stored, with
  `hash_equals`), then reads the payment from Razorpay's Payments API and confirms it only when it is
  `captured` for the right amount. Cancelled or failed checkouts show a message in English and Bengali;
  unfinished checkouts are marked expired after 30 minutes (`php artisan payments:expire`, scheduled).
- **UPI to the club** (fallback when the Razorpay keys are blank): a QR code of
  `upi://pay?pa=<CLUB_UPI_ID>&pn=<CLUB_UPI_NAME>&am=<amount>&cu=INR&tn=<family no. and reference>` and an
  "Open UPI app" button on phones. The member types the 12-digit UTR (unique among live payments, also
  enforced by a partial unique index); the treasurer confirms or rejects it in the console.

The statement (`/app/dues`) shows a financial year month by month (paid, due, checking, waived,
upcoming), and receipts have a printable page (print styles hide the app around it; "Save as PDF" in
the browser's print dialog keeps a copy).

**Razorpay client:** the official `razorpay/razorpay` 2.x SDK installs cleanly on PHP 8.4, but it makes
its HTTP calls with its own library (`rmccue/requests`), which Laravel's `Http::fake()` cannot intercept.
`App\Payments\RazorpayGateway` therefore calls the same documented REST endpoints (`POST /v1/orders`,
`GET /v1/payments/{id}`, HTTP Basic auth `key_id:key_secret`) with Laravel's HTTP client and checks the
signatures exactly as the SDK's `Utility` does. No extra dependency, and every Razorpay call is faked in
the tests.

**Gateway fee:** Razorpay's payment entity has `fee` (paise, including GST) and `tax` (the GST part). When
they are present, the fee is written to the Club book as an expense under *Payment gateway fee*, rounded
to whole rupees, with the exact figure in the note. Income is always the full amount the member paid.

**Refunds** are not done from the app: refund in the Razorpay Dashboard, then cancel the receipt in the
console (the number stays in the register marked cancelled and the khata gets a reversing entry).

### Webhook (`POST /webhooks/razorpay`)

No sign-in, no CSRF token (exempted in `bootstrap/app.php`), rate limited. The `X-Razorpay-Signature`
header is checked against the raw body with `RAZORPAY_WEBHOOK_SECRET`. `X-Razorpay-Event-Id` is stored in
`webhook_events`, so a repeated delivery is answered 200 and ignored. `payment.captured` and `order.paid`
confirm the payment (same code path as the checkout callback), `payment.failed` marks a still-pending
payment failed (a later capture of the same order still confirms it). Unknown events get 200. If
processing fails the answer is 500 and Razorpay retries.

### Console (Treasurer and Admin; policies on every route)

- **Collect payment** (`/admin/collect`): find a family by name, family number or phone, pick how many
  months (the oldest unpaid first, then months in advance), cash or UPI with the UTR, record. The receipt
  is issued at once, with WhatsApp and print buttons and an undo (cancel with a reason).
- **Online payments** (`/admin/payments`): UPI payments waiting for confirmation (Confirm / Reject with a
  reason the member sees), Razorpay payments with their order and payment ids, and payments that need a
  look (money that arrived for months already settled, or an amount mismatch).
- **Import bank statement** (`/admin/payments/import`): see the CSV format below.
- **Dues & reminders** (`/admin/dues`): families who owe, filtered by months overdue (1+, 3+, 6+) and
  lane; paid and waived months by month and lane; "Remind selected" opens WhatsApp `wa.me` links with the
  message filled in (nothing is sent by the app; the last reminder date is kept). Each family's page lists
  every month and can waive months with a reason. "Add dues" (or `php artisan dues:generate --month=2026-12`,
  scheduled on the 1st of every month) adds the month's due from the active monthly fee plan for every
  active family; running it twice adds nothing twice.
- **Receipts register** (`/admin/receipts`): every number in order with a series check (every number from
  0001 to the counter's last number must be there; cancelled receipts stay), a printable receipt page and
  a CSV export.
- **Khata** (`/admin/khata`): the Club, Catering and Nursery books by month, with the balance brought
  forward and a running balance, monthly joma/khoroch, cash in hand and bank, filters by type and head,
  hand-written entries (book, joma or khoroch or a cash deposit/withdrawal, head, cash or bank, note and an
  optional bill photo kept on the private disk), and reversals with a note (entries are never edited or
  deleted). The catering and nursery in-charges see their own book and write their own entries in it.
- **Cash & bank** (`/admin/cash`): cash and bank of each book as the khata says, recent deposits, and UPI
  money still waiting to be matched.
- **Reports** (`/admin/reports`): subscriptions collected month by month (receipts, cash, UPI, online)
  and income and expenditure by head for each book, for the AGM, for a financial year (April to March),
  each with a CSV download (UTF-8 with BOM, opens in Excel).

### Bank statement CSV format (UPI to the club)

Download the account statement from the bank's website as CSV. The first row must be the column names;
comma, semicolon or tab separated. On the next page you choose (the app guesses from the names):

| Column | Example names | Notes |
|---|---|---|
| Money received | `Credit`, `Deposit`, `Amount` | Rupees, `1,200.00` or `₹1200` is fine; blank, zero or negative lines are left out |
| Description | `Description`, `Narration`, `Remarks` | Every 12-digit number in it is tried as a UTR |
| Date (optional) | `Txn Date`, `Value Date` | `15/11/2026`, `15-11-2026`, `2026-11-15`, `15 Nov 2026` |

A line matches when its UTR belongs to a UPI payment waiting for confirmation **and** the amount is the
same. Matched lines are shown first; "Confirm matched payments" confirms them all. Unmatched lines are
listed with the reason (no UTR, no waiting payment, different amount). The file is deleted afterwards.

### Setting up Razorpay (going live)

1. Create a Razorpay account at <https://razorpay.com> (Sign up), and use **Test mode** first.
2. Dashboard → *Account & Settings* → *API Keys* → generate Test mode keys. Put them in `.env`:
   `RAZORPAY_KEY_ID=rzp_test_...` and `RAZORPAY_KEY_SECRET=...`, then `php artisan config:clear`.
   The key ID is public; the secret never leaves the server. Never commit `.env`.
3. Dashboard → *Account & Settings* → *Payment Capture*: keep automatic capture on (Razorpay recommends
   it; authorised payments that are never captured are refunded).
4. Dashboard → *Account & Settings* → *Webhooks* → *Add New Webhook*: URL
   `https://<your-domain>/webhooks/razorpay`, a secret of your choice (put the same in
   `RAZORPAY_WEBHOOK_SECRET`), and the events `payment.captured`, `payment.failed` and `order.paid`.
   The URL must be public HTTPS on port 443; Razorpay cannot reach `localhost`.
5. Test locally through a tunnel, for example `cloudflared tunnel --url http://localhost:8000`, and use
   the tunnel URL for the Test mode webhook (Razorpay suggests `zrok`; some request-bin domains are
   blocked). Test payments: UPI ID `success@razorpay` or `failure@razorpay`, or the test cards in
   Razorpay's docs. Test mode asks for the OTP `754081` when you add or edit a webhook.
6. Complete Razorpay's KYC (activation) with the club's registration and bank details. After approval,
   generate **Live mode** keys, set them in `.env` on the server, and add the webhook again in Live mode
   (Live and Test mode have separate webhooks and secrets).
7. Make sure the scheduler runs (`* * * * * php artisan schedule:run`) for `payments:expire` and the
   monthly `dues:generate`.

Without keys the app keeps working with UPI to the club: set `CLUB_UPI_ID` and `CLUB_UPI_NAME` (or the
UPI ID in Console → Club settings). The sample UPI ID is a placeholder; the app says so while the sample
data setting is on.

## Not built yet

These are later milestones in [PLAN.md](PLAN.md) and have no routes yet: chanda book, passes and the
offline gate scanner, catering orders and roster, nursery counter sales and stock, meetings, polls and
web push, data import and deployment. Also left for later:

- one-time codes by SMS (only the log driver exists until the club has a DLT-registered sender);
- the committee's second sign-in step shown in the prototype;
- refunds through the Razorpay API (refund in the Razorpay Dashboard, then cancel the receipt);
- a second signature for large expenses and cash handovers between collectors (shown in the prototype);
- receipts for the Catering and Nursery books (they come with their screens in M9);
- the panjika calendar (placeholder page);
- editing the gallery albums in the console (they are site settings seeded from the prototype).
