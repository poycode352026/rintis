# Rintis

**Rintis** is a web application by Oneclick that helps small businesses manage their store, product catalog, pre-order sales, customer orders, pickup, delivery, and income.

Sellers can create an account and set up their own store. Customers can browse stores and place orders **without creating an account**. Rintis is a standalone application; it does not require a separate OneClickStore Central application or product activation.

## What you can do

- **Manage a store:** store identity, business category, Indonesian address, map pin, payment methods, and delivery preferences.
- **Build a catalog:** categories, product photos, descriptions, and choices such as size, strength, or extras, including additional prices.
- **Calculate production costs:** a private ingredient and packaging worksheet, unit conversions, HPP per product, and a price suggestion based on a target markup over HPP.
- **Run pre-order sessions:** opening and closing times, session menus, quotas, delivery windows, and optional weekly schedules with different menus for each day.
- **Take customer orders:** multiple products and choices in one checkout, pickup or delivery, cash or QRIS when configured, and an active WhatsApp contact number.
- **Process orders:** seller notifications, bulk preparation, pickup QR scanning, cash collection, and delivery route planning.
- **Track progress:** customers can revisit a private order link, see payment and fulfillment updates, and rate purchased products after completion.
- **Review income:** charts, date and payment filters, and recorded payments and completed sales. This is a sales report, not a bank balance or a net-profit calculation.

The default sales model is **open order / pre-order**, not a full inventory-management system. A catalog can be browsed outside a sales session, but ordering depends on an active session.

## How it works

### Seller flow

1. Register and sign in.
2. Create a store and configure its location, receiving methods, and delivery settings.
3. Add categories and products. Product choices and private production-cost calculations are optional.
4. Open a pre-order session, choose its menu and quotas, and set its schedule and delivery window. Weekly schedules can automate opening.
5. Share the public store link with customers.
6. Process incoming orders individually or in batches. Online-payment orders must be verified before preparation; cash orders follow the cash workflow.
7. For pickup, prepare the order, mark it ready, then scan the customer's pickup QR and complete the handover. For delivery, prepare orders, plan a route, start the trip, and mark deliveries complete.
8. Review sales and recorded payments in Income.

### Customer flow

1. Open a shared store link or discover a store in the directory.
2. Browse the menu and choose products, options, and quantities during an active session.
3. Enter contact details and choose pickup or delivery. Delivery requires an address, a map pin, and a successful delivery quote.
4. Choose an available payment method. QRIS is shown only when its integration is available for that store.
5. Keep the private order link to pay and track progress. The browser also keeps access to order history; guest history is not an account-based, cross-device history.
6. When a pickup order is ready, view the pickup QR and collection address. The directions button opens Google Maps.
7. After completion, optionally rate the products purchased.

QRIS can be paid immediately, with a deadline **15 minutes before the session closes**. Unpaid QRIS orders expire at that deadline. Cash payments use a separate collection workflow. An unfinished order prevents another checkout for the same identified customer/browser.

Delivery choices can include Standard, Priority, and Hemat when the seller enables them. Store settings supply defaults, and a session can override them. Customer delivery progress starts when the seller actually starts the delivery trip; it is not live GPS tracking.

## Technology

| Area | Stack |
| --- | --- |
| Backend | Laravel 13, PHP |
| Frontend | Vue 3, Inertia 3 |
| Styling and assets | Tailwind CSS 4, Vite 8 |
| Application database | PostgreSQL |
| Map interface | Leaflet with configurable map tiles |
| Indonesian region lists | Emsifa's API Wilayah Indonesia |
| Delivery routing | An OSRM-compatible routing service |
| Online payment integration | Midtrans Sandbox |
| Tests | PHPUnit and Node.js test runner |

Laravel handles routes, authentication, validation, authorization, and business operations. Inertia passes page data from Laravel to Vue. Vue renders pages inside the application layout and handles interactions such as forms, cart choices, maps, and countdowns.

## Requirements

Install these before cloning and running the project:

- **Git** to clone the repository.
- **PHP 8.4.1 or newer; PHP 8.5 is recommended** for this checkout. The committed dependency lock requires PHP 8.4.1+, even though the root Composer constraint starts at PHP 8.3.
- **Composer 2** to install PHP dependencies.
- **Node.js 22.12+ with npm** to install and build the frontend. Development has been checked with Node.js 22.
- **A running PostgreSQL server** and a database user allowed to create tables in the application's database. pgAdmin is an optional graphical database client; it does not replace the PostgreSQL server.
- PHP extensions required by Composer and Laravel, including `ctype`, `filter`, `hash`, `mbstring`, `openssl`, `session`, `tokenizer`, `PDO`, and **`pdo_pgsql`**. Enable **`pdo_sqlite`** and `sqlite3` to run the default isolated test suite. `fileinfo`, DOM/XML, and cURL should also be available for uploads, tests, and HTTP integrations.

Check your tools:

```sh
php -v
composer -V
node -v
npm -v
git --version
```

On Windows, use a PHP environment with the required extensions and PostgreSQL, or use WSL. The commands below use a Unix-style shell; PowerShell users can replace `cp` with `Copy-Item`.

## Local installation

### 1. Clone and install dependencies

```sh
git clone https://github.com/poycode352026/rintis.git
cd rintis
composer install
npm ci
cp .env.example .env
```

Use `composer install` and `npm ci` to install the versions recorded in the lock files.

### 2. Create a PostgreSQL database

Using pgAdmin or your PostgreSQL administrator account, create a database and an application user. For example, run the following as a PostgreSQL administrator, replacing the sample password:

```sql
CREATE USER rintis WITH PASSWORD 'replace-with-your-local-password';
CREATE DATABASE rintis OWNER rintis;
```

Set the matching values in `.env`:

```dotenv
APP_NAME=Rintis
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rintis
DB_USERNAME=rintis
DB_PASSWORD=replace-with-your-local-password
```

Keep your real credentials in `.env`, which is excluded from Git.

### 3. Initialize the application

For a **new local installation**, generate the application key, create the tables, and expose public product uploads:

```sh
php artisan key:generate
php artisan migrate
php artisan storage:link
```

If you are restoring an existing database, retain its original `APP_KEY`. Changing the key can make existing encrypted data unreadable. `migrate:fresh` deletes data and is not part of the installation process.

### 4. Start the application

Run these in separate terminals from the project directory:

```sh
php artisan serve
```

```sh
npm run dev
```

For weekly opening schedules and QRIS expiration processing, keep a third terminal running:

```sh
php artisan schedule:work
```

Open **http://localhost:8000**. Choose **Create your store**, register, and follow the store setup. Registration does not require seeding or Central activation.

`composer dev` is also available as the combined development runner. Keep `schedule:work` running separately for scheduled business tasks.

### 5. Optional example account

You can create your own account through the registration page. If you specifically want the configured example account, set a password of at least 12 characters in `.env`:

```dotenv
RINTIS_SEED_ACCOUNT_PASSWORD=your-own-long-local-password
```

Then run:

```sh
php artisan db:seed
```

The current example account is named `OCS`, with email `oneclickstore@gmail.com`. It is a regular Rintis account, not a platform administrator. The seeder updates that account's name and password when repeated. It does not create a sample store, menu, or orders.

## Optional integrations

### Maps and Indonesian regions

Default endpoints are defined in `config/rintis.php` and can be overridden in `.env`:

```dotenv
RINTIS_REGIONS_URL=https://www.emsifa.com/api-wilayah-indonesia/v2
RINTIS_MAP_TILES_URL=https://tile.openstreetmap.org/{z}/{x}/{y}.png
```

Leaflet displays the map; the tile service supplies map images. The region API supplies province, city/regency, and district dropdown data. These are separate services. The browser's geolocation API supplies the **Use current location** coordinates after the user grants permission. Geolocation requires HTTPS or a supported localhost development context.

The default external endpoints need internet access. For deployment, choose a tile provider appropriate for your traffic and its usage policy; a public tile endpoint is not a guaranteed production service.

### Delivery distance and fees

Map tiles alone do not calculate road distance. Delivery quotes and route plans require a routing endpoint:

```dotenv
RINTIS_ROUTING_URL=https://your-routing-service.example/route/v1/driving
RINTIS_ROUTING_DEMO=false
```

Replace the example URL with a working OSRM-compatible provider. The delivery planner also uses its corresponding table service to compare destinations. Set the seller's pickup pin and delivery pricing before testing delivery checkout.

A local/testing-only demo mode exists via `RINTIS_ROUTING_DEMO=true`. It is rate-limited and rejected outside local/testing. It does not start a routing server or automatically fill in the endpoint. Without routing configured, you can test pickup; delivery calculation will report that routing is unavailable.

### Midtrans Sandbox

Basic store setup and cash checkout do not require Midtrans credentials. To test the existing online-payment integration, configure **Sandbox** credentials:

```dotenv
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
```

Keep the Server Key on the backend. Configure the store and session payment methods as needed. The initial integration is restricted to the store slug configured in `config/midtrans.php`, currently `wemeet-coffee`, and the local/testing environments. Simply adding keys does not enable online payments for every seller.

The notification endpoint is:

```text
POST /payments/midtrans/notification
```

Midtrans must be able to reach a public HTTPS endpoint to send webhook notifications. A localhost URL is not publicly reachable. Use a suitable development tunnel for webhook testing and configure the resulting URL in the Sandbox dashboard. The application also includes payment-status checks while the order page is open.

Use the Midtrans Sandbox simulator when testing payments. This repository does **not** implement live payment activation or automatic settlement into each seller's bank account. Adding a bank account in store settings does not connect that account to a payment-gateway payout destination.

## Project structure

```text
app/
  Http/Controllers/        HTTP requests and Inertia responses
    Admin/                Seller workspace controllers
    Auth/                 Registration and login
  Http/Requests/          Form validation and authorization
  Models/                 Database models and relationships
  Policies/               Store ownership and authorization
  Services/               Checkout, payments, delivery, costing, and reports
  Notifications/          Seller order/payment activity
config/
  rintis.php              Region, map, routing, timezone, and seed configuration
  midtrans.php            Sandbox payment configuration
routes/
  web.php                 Home, authentication, and legacy link compatibility
  store.php               Storefront, checkout, and seller routes
  console.php             Scheduled business tasks
resources/js/
  Pages/Admin/            Seller pages
  Pages/Storefront/       Public store, checkout, and order pages
  Pages/Auth/             Login and registration forms
  Components/             Reusable UI components
  Layouts/AppLayout.vue   Shared navigation and page layout
  Support/                Cart, costing, formatting, and other frontend helpers
database/migrations/      Versioned database schema
tests/                    Backend and frontend tests
```

### Main URLs

| URL | Purpose |
| --- | --- |
| `/` | Opens the directory for guests or the seller workspace for signed-in users |
| `/explore` | Public store directory |
| `/register` | Create an account |
| `/login` | Sign in |
| `/admin` | Open or set up your own store |
| `/stores/{slug}` | Public store catalog |
| `/stores/{slug}/open-order` | Current pre-order menu |
| `/my-orders` | Customer order history available to the current identity/browser |

`Admin` refers to the seller's own store workspace. There is no Central/platform admin panel. Historical migrations and table prefixes are retained for existing database compatibility; they do not require another application. Previously shared `/rintis/...` links redirect to the standalone URLs.

## Tests and build

```sh
php artisan test
node --test tests/Frontend/*.test.js
npm run build
```

Backend tests use an in-memory SQLite database configured in `phpunit.xml`, not your development PostgreSQL data. Some tests exercise external-service behavior with fakes; passing them does not verify live gateway or routing availability.

The production build is written to `public/build`. During local development, keep Vite running to see source changes. If using built assets instead, run `npm run build` again after frontend changes.

## Common setup issues

| Problem | What to check |
| --- | --- |
| `could not find driver` | Enable `pdo_pgsql` for PostgreSQL or `pdo_sqlite` for the test suite in the PHP configuration used by the CLI. |
| Database connection refused | Start PostgreSQL and verify `.env` host, port, database, and credentials. |
| Missing application key | Run `php artisan key:generate` only for a new installation. |
| Missing product images | Run `php artisan storage:link`; ensure `storage` and `bootstrap/cache` are writable. A clone does not contain another installation's uploads. |
| Frontend changes do not appear | Keep `npm run dev` running, or rebuild assets with `npm run build`. |
| Configuration changes do not apply | Run `php artisan config:clear` and restart long-running development processes. |
| Delivery quotes are unavailable | Configure a working routing endpoint and valid store/customer map pins, or test pickup first. |
| Weekly schedules do not open sessions | Run the scheduler and check the saved schedule and previous unfinished orders. |

## Current scope and deployment

This project is under active development. It includes working store and order flows, but deployment still requires an appropriate server, PostgreSQL, persistent uploads, HTTPS, scheduled tasks, backups, and production-ready external integrations.

Cash operations and recorded income are separate from gateway settlement. Live QRIS, independent seller merchant onboarding, payouts, and refund automation require additional implementation and provider configuration. Delivery progress animations are status indicators, not a courier-location feed.

For detailed product decisions and implementation history, see [Product notes](docs/Rintis.md) and [Progress log](docs/Progress.md). Those working documents are currently written in Indonesian.
