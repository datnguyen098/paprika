# Paprika

Paprika is a Laravel restaurant ordering and booking application for Paprika Patras, a Vietnamese and Greek food restaurant in Greece. The current product direction is Paprika-only: old Dan Huong Chay data, categories, and screens should not be reintroduced.

The storefront supports menu browsing, product customization, AJAX cart updates, checkout, Viva payments, delivery/pickup/dine-in flows, reservations, multilingual content, SEO pages, blog, gallery, contact, and chat. The admin panel manages content, dishes, option settings, orders, reservations, tables, branches, delivery rules, roles, users, translations, and global website settings.

## Stack

- Laravel 13
- PHP 8.3+
- Blade MVC
- Tailwind CSS 4 via Vite
- MySQL or SQLite for local development
- Database queues and sessions
- PHPUnit feature tests
- Viva payment gateway integration

## Main Modules

- Storefront: home, about, menu, dish detail, cart, checkout, reservation, gallery, blog, contact, chat.
- Ordering: product cards, product detail modal, dish option groups, line item customization summary, AJAX add/update/remove cart actions.
- Checkout: delivery, pickup, and dine-in order modes with branch-aware delivery constraints.
- Delivery: per-branch minimum order amount, free delivery threshold, max delivery distance, and distance-based delivery fee zones.
- Payments: offline payment fallback and Viva checkout return/webhook flow.
- Reservations: customer booking form, table availability lookup, 15-minute table hold, reservation workflow status, admin-created bookings.
- Tables: branch-owned restaurant tables with code, name, seat count, zone, joinable flag, sort order, and status.
- Admin: RBAC, dashboard, CMS, dishes, categories, option presets, orders, reservations, tables, branches, translations, SEO, and website identity.
- Localization: Vietnamese, English, and Greek storefront routes/content.

## Recent Changes (2026)

This section is a high-level “what changed” overview for the most recent work.

### Storefront: order tracking pages + emails

- Added customer order lookup and tracking pages.
  - Views: `resources/views/storefront/orders/lookup.blade.php`, `resources/views/storefront/orders/track.blade.php`
  - Controller: `app/Http/Controllers/Storefront/OrderTrackingController.php`
  - Routes: `routes/web.php`
- Added customer order emails (confirmation + payment confirmed).
  - Views: `resources/views/emails/customer-order-confirmation.blade.php`, `resources/views/emails/customer-payment-confirmed.blade.php`
  - Translations: `resources/lang/{vi,en,el}/emails.php`

### Checkout UX: timeslot guard + shipping-fee guard

- Added a checkout availability check that reacts immediately when the user changes date/time/branch.
  - Disables submit when the selected time makes some cart items unavailable.
  - Highlights unavailable items in the right-side summary.
  - Adds a “Back to cart” action next to the warning.
- Restored and hardened the “shipping fee not calculated yet” modal guard (delivery mode).

### Storefront menu/product cards: add-to-cart enablement

- Standardized add-to-cart behavior across menu cards and dish detail so buttons don’t appear disabled unexpectedly.
  - View: `resources/views/storefront/components/product-card.blade.php`

### Admin: bulk actions polish

- Orders list: fixed responsive UI for bulk cancel/delete action bar.
  - View: `resources/views/admin/orders/index.blade.php`
- Bulk dish time slots: when branch is not selected, the UI now shows an explicit message instead of silently doing nothing.
  - View: `resources/views/admin/dishes/bulk-time-slots.blade.php`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

Default local URL:

```text
http://127.0.0.1:8000
```

Admin URL:

```text
http://127.0.0.1:8000/admin
```

Seeded admin account:

```text
Email: admin@paprika-patras.gr
Password: password
```

## Local Database

The example environment uses SQLite by default. Create the local SQLite file before migrating if it does not exist:

```powershell
New-Item -ItemType File database/database.sqlite -Force
php artisan migrate --seed
```

For MySQL, update these values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paprika
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate:fresh --seed
```

## Daily Development

Run Laravel, Vite, queue listener, and logs together:

```bash
composer run dev
```

Or run them separately:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
php artisan pail
```

Build frontend assets:

```bash
npm run build
```

Run tests:

```bash
php artisan test
```

Clear common caches after route/config/view changes:

```bash
php artisan optimize:clear
```

## Environment

Important `.env` values:

```env
APP_NAME=Paprika
APP_URL=http://127.0.0.1:8000
APP_LOCALE=vi
APP_FALLBACK_LOCALE=vi
APP_TIMEZONE=Asia/Ho_Chi_Minh

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
UPLOAD_DISK=public

VIVA_ENV=demo
VIVA_CLIENT_ID=
VIVA_CLIENT_SECRET=
VIVA_MERCHANT_ID=
VIVA_API_KEY=
VIVA_WEBHOOK_VERIFICATION_KEY=
VIVA_SOURCE_CODE=
VIVA_CURRENCY=EUR
VIVA_COUNTRY_CODE=GR
VIVA_REQUEST_LANG=el-GR

GEOAPIFY_API_KEY=
GEOAPIFY_COUNTRY_CODE=gr
GEOAPIFY_ROUTING_MODE=drive
```

Viva checkout requires `VIVA_CLIENT_ID`, `VIVA_CLIENT_SECRET`, and `VIVA_SOURCE_CODE`. Webhook verification also requires the demo/live `VIVA_MERCHANT_ID` and `VIVA_API_KEY` from Viva API Access. Use `VIVA_ENV=demo` for sandbox testing and `VIVA_ENV=production` only when the merchant account is ready. `VIVA_WEBHOOK_VERIFICATION_KEY` can be set as an explicit override if Viva's live key endpoint is temporarily unavailable or the key has been retrieved outside the app.

In Viva's Website/App payment source for the configured `VIVA_SOURCE_CODE`, set:

```text
Success URL: https://www.paprikapatras.com/payments/viva/return
Failure URL: https://www.paprikapatras.com/payments/viva/failure
Webhook URL: https://www.paprikapatras.com/payments/viva/webhook
Webhook events:
- Transaction Payment Created
- Transaction Failed
```

Keep Viva's default redirect parameter names (`s`, `t`, `lang`, `eventId`). The app verifies the payment status again after Viva redirects or sends the webhook; do not rely only on the browser redirect. Viva may redirect failed Smart Checkout attempts without a `t` transaction parameter, so the `Transaction Failed` webhook is required to receive the failed transaction ID and detailed payload.

Geoapify is optional unless a branch enables automatic delivery quote. Keep `GEOAPIFY_COUNTRY_CODE=gr` for Paprika Patras and use `drive` routing for motorbike/car delivery estimates.

## Money And Currency

The restaurant currency is EUR. Monetary values are stored as integer minor units:

```text
950 = EUR 9.50
150 = EUR 1.50
0   = EUR 0.00
```

Use existing formatting helpers instead of manually concatenating currency strings.

## Storefront Routes

Vietnamese default routes:

- `/` home
- `/gioi-thieu` about
- `/thuc-don` menu
- `/mon-an/{slug}` dish detail
- `/gio-hang` cart
- `/dat-hang` checkout
- `/dat-ban` reservation
- `/khong-gian` gallery
- `/lien-he` contact

English routes live under `/en`, for example `/en/menu`, `/en/cart`, `/en/checkout`.

Greek routes live under `/el`, for example `/el/menou`, `/el/kalaithi`, `/el/tameio`.

## Data Model Notes

- `Branch` stores restaurant branch data, contact details, opening/reservation overrides, delivery eligibility, minimum order rules, max delivery distance, and delivery zones.
- `BranchDeliveryZone` stores distance bands and fees for each branch.
- `RestaurantTable` stores the actual tables available for booking per branch.
- `Reservation` stores customer booking details, workflow status, hold expiry, branch, and selected table.
- `Dish` belongs to a menu `Category` and can have multiple `DishOptionGroup` records.
- `DishOptionGroup` supports single-choice, multi-choice, and exclude-style options.
- `Order` stores checkout customer data, mode, totals, payment status, and shipment.
- `OrderItem` stores the selected dish, quantity, selected options, option price deltas, and notes.

## Paprika Seed Data

The main seed file is:

```text
database/seeders/PaprikaPatrasSeeder.php
```

It creates:

- Paprika site settings and identity assets.
- One active Patras branch.
- Restaurant tables for the branch.
- Paprika gallery/banner/promotion/page records.
- Menu categories: Vietnamese Food, Greek Food, Drinks.
- Paprika menu items and dish option groups.
- Delivery fee zones for the Patras branch.

`AdminUserSeeder` removes the old legacy admin account and creates the Paprika admin account.

## Branch And Delivery Settings

See admin:

- Branch configuration: `/admin/branches`
- Delivery rules & zones: `/admin/delivery-rules`
