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
`spatie/laravel-activitylog` for the audit trail, Laravel Pint, Larastan and PHPUnit.

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
serving packages, the committee list, notices and 24 sample families. All names, phone numbers,
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
