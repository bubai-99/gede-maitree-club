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
