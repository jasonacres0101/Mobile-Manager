# Mobile Manager

Laravel 12 customer and admin portal for customer SIM management, ConnectWise PSA invoice visibility, and GoCardless Direct Debit collection.

ConnectWise PSA remains the source of truth for companies, agreements, additions/SIMs, and invoices. This app only syncs agreements whose ConnectWise agreement type ID is listed in `CONNECTWISE_SIM_AGREEMENT_TYPE_IDS`.

## Stack

- Laravel 12
- Laravel Breeze Blade authentication
- Laravel Fortify two-factor authentication
- MySQL
- Tailwind CSS and Vite
- Database queue jobs
- Laravel scheduler
- Laravel HTTP client for ConnectWise PSA
- GoCardless PHP SDK

## Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Build frontend assets:

```bash
npm run build
```

For local development:

```bash
npm run dev
php artisan serve
```

## Environment

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sim_portal
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

Configure ConnectWise:

```env
CONNECTWISE_BASE_URL=https://api-na.myconnectwise.net/v4_6_release/apis/3.0
CONNECTWISE_COMPANY_ID=
CONNECTWISE_PUBLIC_KEY=
CONNECTWISE_PRIVATE_KEY=
CONNECTWISE_CLIENT_ID=
CONNECTWISE_SIM_AGREEMENT_TYPE_IDS=12,18
```

`CONNECTWISE_SIM_AGREEMENT_TYPE_IDS` is the allow-list for SIM agreement types. The sync builds this ConnectWise condition dynamically:

```text
type/id=12 OR type/id=18
```

No command in this app syncs all agreements.

For setup, create a dedicated ConnectWise PSA API member, generate public/private API keys, and enter the base URL, company ID, public key, private key, client ID, and SIM agreement type IDs in `/admin/settings?tab=connectwise`.

Only add agreement type IDs that are genuinely for SIM agreements. The sync must stay limited to the configured SIM agreement type allow-list.

Configure GoCardless:

```env
GOCARDLESS_ACCESS_TOKEN=
GOCARDLESS_ENVIRONMENT=sandbox
GOCARDLESS_WEBHOOK_SECRET=
```

Admins can also manage GoCardless, ConnectWise PSA, and Mobile Manager / Jola settings in the portal at `/admin/settings`. Each provider has its own tab and saves independently. Values saved there are stored in the database, with secrets encrypted, and take priority over `.env` values.

Configure Mobile Manager / Jola:

```env
MOBILEMANAGER_BASE_URL=https://developers.mobilemanager.co.uk
MOBILEMANAGER_API_KEY=
MOBILEMANAGER_API_SECRET=
```

Configure Microsoft 365 welcome email:

```env
MICROSOFT365_TENANT_ID=
MICROSOFT365_CLIENT_ID=
MICROSOFT365_CLIENT_SECRET=
MICROSOFT365_SENDER_EMAIL=
```

## Database

Run migrations:

```bash
php artisan migrate
```

Create users through Breeze registration, then set roles/company links in the database or through your own admin seeding process:

- `users.role`: `admin` or `customer`
- Customer users must have `company_id`
- Admin users can have `company_id` as `null`

## Queue Worker

Sync commands dispatch queued jobs. Run a worker:

```bash
php artisan queue:work
```

## Scheduler

Add the Laravel scheduler cron entry:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:

- `sync:connectwise-sim-agreements` hourly
- `sync:connectwise-invoices` every 30 minutes
- `payments:collect-due-gocardless` daily at 08:00

## Sync Commands

Sync SIM agreements, SIM additions, and invoices:

```bash
php artisan sync:connectwise-sim-agreements
```

Refresh invoices for existing SIM agreements only:

```bash
php artisan sync:connectwise-invoices
```

The agreement sync uses ConnectWise pagination and always requests only configured SIM agreement types from PSA. Each queued agreement job re-checks the type allow-list before writing data.

Sync Jola SIMs from Mobile Manager:

```bash
php artisan sync:jola-sims
```

The Mobile Manager / Jola integration is read-only for safety. `App\Services\MobileManagerService` only implements GET requests for customers, customer SIMs, SIMs, and orders. It does not implement order creation, activation, cease, bar, unbar, SIM swap, tariff changes, SMS sending, network sync, customer updates, SIM updates, or any other provisioning/write action.

## Microsoft 365 Welcome Email

Admins can configure Microsoft 365 from `/admin/settings?tab=microsoft365`. The app uses Microsoft Graph OAuth client credentials, not basic SMTP authentication.

In Microsoft Entra admin center, create an app registration, add Microsoft Graph application permission `Mail.Send`, grant admin consent, then save the tenant ID, application client ID, client secret, and sender mailbox in Settings.

If Microsoft asks what you want to do with the application, choose:

```text
Register an application to integrate with Microsoft Entra ID (App you're developing)
```

Do not choose Application Proxy or Non-gallery application for this portal email integration.

From the Users tab, admins can create a user and tick `Send welcome email so the user creates their own password`, or send a fresh welcome email from the user list. The email contains Laravel's password creation/reset link.

## Two-Factor Authentication

The portal uses Laravel Fortify for authenticator-app 2FA. Users can enable it from `Profile` after logging in.

Recommended authenticator app:

- Microsoft Authenticator

The setup flow shows a QR code, asks the user to confirm the 6-digit authenticator code, and then displays recovery codes. Store recovery codes safely because each code can only be used once.

Run the Fortify migration after deployment:

```bash
php artisan migrate
```

This adds encrypted 2FA secret and recovery-code columns to the `users` table.

## Auto Collection

Admins can configure GoCardless auto collection per company from the company detail page. The scheduler runs `payments:collect-due-gocardless` daily and only collects invoices when:

- Auto collection is enabled for the company.
- The company has an active or submitted mandate.
- The invoice has a positive balance and a due date.
- No GoCardless payment is already linked.
- The due date minus the configured number of days has arrived.
- The balance is within the configured minimum and optional maximum amount.

Preview eligible invoices without creating payments:

```bash
php artisan payments:collect-due-gocardless --dry-run
```

## GoCardless Sandbox

1. Create a GoCardless sandbox access token.
2. Set `GOCARDLESS_ENVIRONMENT=sandbox`.
3. Configure the webhook endpoint in GoCardless:

```text
https://your-domain.test/webhooks/gocardless
```

4. Copy the webhook endpoint secret into `GOCARDLESS_WEBHOOK_SECRET`.
5. Customers can start mandate setup at `/customer/direct-debit/setup`.
6. Admins can collect invoice payments from `/admin/invoices`.

## Portals

Admin routes require `role=admin`:

- `/admin`
- `/admin/companies`
- `/admin/agreements`
- `/admin/sims`
- `/admin/jola-sims`
- `/admin/invoices`
- `/admin/payments`

Customer routes require `role=customer` and only use the authenticated user's `company_id`:

- `/customer`
- `/customer/sims`
- `/customer/invoices`
- `/customer/direct-debit/setup`

## Security Notes

- API keys are read only from environment variables.
- Role middleware separates admin and customer portals.
- Customer controllers scope all data through the authenticated user's company.
- GoCardless webhooks verify `Webhook-Signature` before storing or processing events.
- Webhook events are stored and skipped if already processed.
