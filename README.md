# Bomaveda
Anonymous, care-first youth support for Kilifi County. Web (PWA) and USSD share one engine.

## Pages
`/` home, `/submit-concern`, `/request-support`, `/resources`, `/case` (case number lookup), `/auth` and `/admin` (coordinator workspace), `/simulator` (USSD phone demo).

## Run locally
1. `copy .env.example .env` and fill APP_KEY and the passwords (see comments in the file).
2. `docker compose up --build`, then open http://localhost:8080
3. The database schema loads only on an empty database. After a schema change run `docker compose down -v` first (this deletes test data).

## Staff accounts (no public sign-up)
`docker compose exec web php bin/create-staff.php <username> <admin|coordinator|liaison> ["Kilifi North"]`
It prints a password and an authenticator key once. Add the key to an authenticator app. Sign in at /auth.

## Test (creates and removes its own test data; never run on real data)
`docker compose exec web php -d allow_url_fopen=1 tests/security_test.php`
`docker compose exec web php -d allow_url_fopen=1 tests/staff_test.php`

## USSD gateway
Set USSD_TOKEN in .env, then give your gateway: `https://YOUR-DOMAIN/ussd.php?k=YOUR_USSD_TOKEN`. Only the `text` field is read. Shortcode shown in the demo: USSD_CODE (now *519*41# until one is assigned).

## Where things live
- `app/data.php` categories, wards, agencies, partners, guides, emergency numbers. `app/i18n.php` all wording (English, Kiswahili).
- `app/pages/` one file per page. `app/ussd.php` USSD menus. `app/lib.php` and `app/auth.php` shared code.
- `public/` front controller and static files. `db/schema.sql` database. `docker/` PHP and Apache hardening. `tests/` security tests.

## Public server
`docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build` (set DOMAIN in .env first). Set SIM_ENABLED=0 for real use.
