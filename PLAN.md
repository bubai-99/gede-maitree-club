# Gede Maitree Club — build plan (Laravel)

The real application behind the clickable prototype in the design repo
(`maitri-club`, folder `Design/mock`). The prototype is the reference for
every screen, word and colour; this repo turns it into a working,
database-backed app.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel (latest stable), PHP 8.4 |
| Database | PostgreSQL 17 (tests run against a separate PostgreSQL database) |
| Front end | Blade, Alpine.js, Tailwind CSS 4, built with Vite |
| Plain JS modules | Heavy screens (CMS editor, gate scanner, ID card) stay plain ES modules |
| Sliders | CSS scroll-snap for swipe rows; Swiper only for a hero or lightbox |
| Roles | `spatie/laravel-permission` |
| Media | `spatie/laravel-medialibrary` |
| Audit | `spatie/laravel-activitylog` |
| Languages | English and Bengali: `lang/en.json`, `lang/bn.json`, `__()` in Blade |
| Quality | Laravel Pint, Larastan, PHPUnit feature tests, GitHub Actions CI |
| Install | PWA: two web manifests (member, committee) and a service worker |

## Surfaces

One codebase, four surfaces, each with its own layout and sign-in:

- **Website** `/` — public, bilingual, SEO.
- **Member app** `/app` — families: dues, pay, receipts, ID card, events, notices.
- **Committee console** `/admin` — members, fees, khata, events, catering, nursery, website CMS.
- **Gate scanner** `/admin/scan` — offline-first pass checking.

## Roles

| Role | Can do |
|---|---|
| Admin (Secretary) | Everything, manages roles |
| Treasurer | Members, fees, all three books, reports |
| Puja in-charge | Events, chanda book, passes |
| Catering in-charge | Catering orders, Catering book (own entries) |
| Nursery in-charge | Catalogue, stock, counter sale, Nursery book (own entries) |
| Volunteer | Gate scanner, record chanda (own area) |
| Website editor | Website CMS only |
| Member | Own profile, family, dues, pay, ID, events, notices, polls |

Enforced on the server (middleware, policies, gates), never only in the UI.

## Milestones

Each milestone ends with: tests green, Pint clean, Larastan clean,
`npm run build` passing, one commit. Done so far: M0 to M7.

| # | Milestone | Done when |
|---|---|---|
| M0 | Skeleton & tooling | Laravel installed, PostgreSQL connected, Pint, Larastan, CI workflow, README with setup steps |
| M1 | Design system & layouts | Prototype tokens and fonts in Tailwind 4, Blade components, site / member / admin layouts, language switch |
| M2 | Data model | Migrations, models, factories and seeders for every table below; sample data labelled as sample |
| M3 | Sign-in & roles | Member and committee sign-in kept apart, password reset, lockout, roles and policies |
| M4 | Public website | Every prototype website screen as a real route, data from the database, forms saved with validation and rate limits, SEO, sitemap |
| M5 | Console & website CMS | Console shell and dashboard; CMS for pages, events, plants, packages, notices, contact, committee, menu, media; draft → publish; activity log |
| M6 | Member app core | Home, dues, receipts, ID card, events, notices, profile; PWA manifests and service worker |
| M7 | Fees & khata ✅ | Cash entry, online payments with Razorpay Standard Checkout (UPI to the club with a UTR as fallback), UPI confirm and bank statement matching, receipts with numbering, three books, reports |
| M8 | Events, chanda, passes, scanner | Chanda book, sponsors, passes with QR, offline scanner |
| M9 | Catering & nursery | Enquiry → quote → advance → bill; roster; counter sale and stock |
| M10 | Meetings, polls, push | Notices to members, AGM polls, web push |
| M11 | Launch | Data import, backups, deploy to a VPS, HTTPS |

## Data model (first cut)

```
users             id, name, email, phone, password, locale, family_id?, is_committee, last_login_at
families          id, family_no, head_name, address, area, phone, status, joined_on
members           id, family_id, name, name_bn, relation, dob, phone, blood_group
fee_plans         id, name, amount, period (monthly|yearly), admission_fee
dues              id, family_id, period, amount, status (due|paid|waived), waived_reason, waived_by, waived_at
payments          id, family_id, amount, mode (cash|upi|online), gateway (razorpay|manual_upi), reference,
                  gateway_order_id, gateway_payment_id, gateway_method, gateway_fee_paise, gateway_tax_paise,
                  upi_ref (UTR), received_by, receipt_no, status, note, paid_at, submitted_at, cancelled_at/by
payment_due       payment_id, due_id
books             id, code (club|catering|nursery)
ledger_entries    id, book_id, entry_date, type (income|expense|deposit|withdrawal), account (cash|bank), head,
                  amount, note, source_type, source_id, recorded_by, reverses_id (+ bill photo, medialibrary)
webhook_events    id, provider, event_id, event, payload, processed_at   (Razorpay webhooks already handled)
receipt_counters  book_code, year, last_no          (MC-25-0142, CT-25-0018, NS-25-0311)
events            id, slug, title, title_bn, kind, starts_at, time_label(_bn), place(_bn), pass_required,
                  contact_role, chanda_box, about(_bn), schedule (json), bring (json), status, published_at
chanda_entries    id, event_id, donor_name, area, amount, collected_by, receipt_no
sponsors          id, event_id, name, amount, deliverable
passes            id, event_id, member_id, qr_token, used_at, scanned_by
catering_packages id, name(_bn), for(_bn), rate, min_guests, popular, menu (json), menu_bn (json), sort
catering_orders   id, customer_name, phone, is_member, event_date, guests, package_id, venue, status
catering_roster   id, order_id, server_name, phone, is_leader, wage, confirmed
catering_payments id, order_id, amount, kind (advance|final), mode
plants            id, slug, name(_bn), category, pot(_bn), price, member_price, stock, is_new, light, water,
                  about(_bn), tip(_bn), active
stock_moves       id, plant_id, qty, kind (in|sale|loss|adjust), ref
nursery_sales     id, buyer_name, member_id, total, mode, receipt_no
nursery_sale_items id, sale_id, plant_id, qty, rate
notices           id, slug, title(_bn), body(_bn), audience (public|members), pinned, published_at
meetings          id, kind (committee|agm), held_on, minutes, attendees
polls, poll_votes id, meeting_id, question, options, closes_at / poll_id, member_id, choice
committee_members id, name(_bn), role, role_bn, phone, photo, sort
pages             id, key, title(_bn), blocks (json), draft (json), status, published_at   (CMS)
site_settings     key, value (json)          (contact, menu, SEO, photos)
enquiries         id, kind (contact|join|catering|volunteer|photos), name, phone, payload (json), handled_at
activity_log      (spatie)
```

## Rules

- Every user-facing string goes through `__()` with both languages filled.
- Money is stored in whole rupees as integers (paise only at the Razorpay boundary).
- Sample data stays marked as sample. No real member data in seeders.
- Secrets live in `.env` only. `.env` is never committed.
