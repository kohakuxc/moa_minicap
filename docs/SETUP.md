# Oculo: clean and secure GitHub + Supabase setup

This project uses:

- **Laravel** for the server and application rules
- **PostgreSQL on Supabase** for persistent data
- **Prisma** for the patient, appointment, and prescription schema
- **GitHub Actions** to validate and apply committed Prisma migrations

The repository can never be functionally connected to your Supabase project
until you add your private Supabase connection strings as GitHub Secrets. Never
send those values in chat, commit them, or place them in frontend JavaScript.

## 1. Install the local tools

On Windows, install:

1. Git for Windows: https://git-scm.com/download/win
2. Laravel Herd for Windows: https://herd.laravel.com/windows
3. Node.js LTS: https://nodejs.org/en/download
4. An ngrok account and the Windows client: https://ngrok.com/download/windows

Herd supplies PHP and Composer. Supabase supplies the hosted PostgreSQL
database, so local PostgreSQL is not required.

Open a new PowerShell window and verify:

```powershell
php --version
composer --version
node --version
npm --version
git --version
ngrok version
```

## 2. Create and harden the Supabase project

1. Create a project at https://supabase.com/.
2. Choose a region close to the application users.
3. Use a strong, unique database password and save it in a password manager.
4. Turn on MFA for the Supabase account.
5. Open **Project Settings > Database > Connect**.
6. Copy the **session pooler** connection string for application traffic.
7. Copy the **direct** connection string for Prisma migrations.
8. Confirm that the database is PostgreSQL and that the project is not paused.

For real patient or prescription data, review your local privacy and healthcare
requirements before production use. Use fake data while developing. A hosted
database does not automatically make an application compliant.

## 3. Clone the repository and install dependencies

If the repository is not on your computer yet:

```powershell
cd D:\
git clone https://github.com/kohakuxc/moa_minicap.git MOASystem
cd D:\MOASystem
```

If it is already present:

```powershell
cd D:\MOASystem
git pull origin main
```

Install the PHP and JavaScript dependencies:

```powershell
composer install
npm install
```

Create the local environment:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

## 4. Configure the local Laravel connection

Open `.env` in an editor. `.env` is ignored by Git and must remain local.
Replace every placeholder below with values copied from Supabase:

```dotenv
APP_NAME=Oculo
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=your-supabase-pooler-host
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=your-supabase-pooler-username
DB_PASSWORD=your-supabase-database-password

# Use the session pooler URL for Laravel runtime connections.
DATABASE_URL="postgresql://your-supabase-pooler-username:your-supabase-database-password@your-supabase-pooler-host:5432/postgres?schema=public"

# Use the direct URL for Prisma migrations.
DIRECT_URL="postgresql://postgres:your-supabase-database-password@your-supabase-direct-host:5432/postgres?schema=public"
```

Use the exact host, username, and port shown by Supabase. If a password
contains characters such as `@`, `:`, `/`, or `#`, URL-encode it inside
`DATABASE_URL` and `DIRECT_URL`; the individual `DB_PASSWORD` value should
remain the normal password.

After changing `.env`, clear cached Laravel configuration:

```powershell
php artisan config:clear
```

Do not put either connection string in `resources/`, `public/`, Blade markup,
browser JavaScript, screenshots, issues, or pull requests.

## 5. Generate the first Prisma migration locally

The repository contains `prisma/schema.prisma`, but the initial migration is
created only after you provide a real database connection. Run:

```powershell
npx prisma generate
npx prisma validate
npx prisma migrate dev --name init
```

Confirm that a new `prisma/migrations/` directory was created. Inspect the SQL
before committing it. It should create the `Patient`, `Appointment`, and
`Prescription` tables and the required indexes/foreign keys.

Check the migration status:

```powershell
npx prisma migrate status
```

Then inspect the new tables in Supabase **Table Editor**. Do not edit the
database manually after migration in normal development. Change
`prisma/schema.prisma`, generate a named migration, review it, and commit it.

Commit only schema and migration files:

```powershell
git add prisma package.json package-lock.json
git commit -m "Add initial Supabase schema migration"
git push origin main
```

Never commit `.env`, `.env.*` files containing credentials, database dumps, or
service-role keys.

## 6. Add encrypted GitHub Actions secrets

The GitHub repository is:

https://github.com/kohakuxc/moa_minicap

1. Open the repository.
2. Go to **Settings > Secrets and variables > Actions**.
3. Click **New repository secret**.
4. Add `SUPABASE_DATABASE_URL`.
   - Value: the Supabase session pooler connection string used by runtime jobs.
5. Add `SUPABASE_DIRECT_URL`.
   - Value: the Supabase direct connection string used by Prisma migrations.

GitHub encrypts these values and exposes them only to workflows that reference
them. Do not add them as ordinary repository variables. Do not add Supabase
`anon` or `service_role` API keys for this Prisma workflow.

For a team, prefer environment secrets:

1. Create a GitHub environment named `production`.
2. Add required reviewers.
3. Store the two secrets under that environment.
4. Add `environment: production` to the migration job after reviewing the
   workflow and deciding who can approve production schema changes.

## 7. Understand the GitHub Actions workflow

The workflow at
[`.github/workflows/supabase.yml`](../.github/workflows/supabase.yml) runs when
Prisma files are pushed to `main`, or when manually started in the **Actions**
tab. It:

1. Checks out the repository.
2. Installs Node.js.
3. Installs dependencies.
4. Validates the Prisma schema.
5. Runs `prisma migrate deploy` against Supabase.

`migrate deploy` applies committed migrations; it does not create migrations
from schema changes. Always create and review migrations locally first.

After pushing the first migration:

1. Open **Actions > Supabase schema**.
2. Open the new workflow run.
3. Confirm both validation and migration steps are green.
4. Confirm the tables in Supabase.

If the workflow fails, do not repeatedly rerun it blindly. Read the failing
step, check the connection secret, and verify that the migration has not
already been applied.

## 8. Run Laravel locally against Supabase

Start the application:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

In another PowerShell window, verify the database and routes:

```powershell
php artisan about
php artisan migrate:status
php artisan route:list
Invoke-WebRequest http://127.0.0.1:8000
```

The landing page should return HTTP 200. Do not use `php artisan migrate:fresh`
against Supabase unless you deliberately intend to destroy all tables and
development data.

## 9. Secure application behavior

Before accepting real clinic data:

- Set `APP_DEBUG=false` in production.
- Use HTTPS for the deployed Laravel application.
- Keep `.env` outside Git and rotate any credential that was exposed.
- Use Laravel authorization policies for patient/admin access.
- Validate every appointment and prescription input server-side.
- Never trust a patient ID supplied by the browser to authorize record access.
- Add audit logging for prescription and appointment changes.
- Restrict database access to the server; do not connect to PostgreSQL from
  browser code.
- Enable Supabase backups and verify that a restore is possible.
- Use fake patient data during development and demos.
- Do not expose a Supabase `service_role` key to users.

If the Supabase Data API is enabled, enable Row Level Security on any tables
accessed through that API and write/test policies. If Laravel is the only
database client, keep the database credentials server-side and enforce access
through Laravel.

## 10. Deploying Laravel

GitHub stores the source code and migrations, but it does not run the Laravel
application. Deploy Laravel to a PHP host such as Laravel Cloud, Forge-backed
infrastructure, or another PHP-compatible service.

Set these production environment values on the hosting provider, not in Git:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
DB_CONNECTION=pgsql
DB_HOST=your-supabase-pooler-host
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=your-supabase-pooler-username
DB_PASSWORD=your-supabase-database-password
DATABASE_URL=your-supabase-pooler-url
DIRECT_URL=your-supabase-direct-url
```

Run the provider's deployment build and health checks, then verify the deployed
application with a harmless read-only request. Keep database migrations in the
GitHub Actions workflow rather than running destructive commands on every web
server restart.

## 11. Test on a phone with ngrok

With Laravel running locally:

```powershell
ngrok config add-authtoken YOUR_NGROK_TOKEN
ngrok http 8000
```

Open the displayed HTTPS forwarding URL on your phone. Do not use the ngrok
URL as a permanent production domain. Never commit the ngrok token.

## 12. Troubleshooting

- **`php` is not recognized:** reopen PowerShell or confirm Laravel Herd is
  installed and active.
- **`could not find driver`:** enable PHP's `pdo_pgsql` extension in the PHP
  installation used by `php --ini`.
- **`password authentication failed`:** copy the Supabase database password
  again and confirm the pooler username, host, and port.
- **Prisma `P1001`:** check network access and use the Supabase session pooler
  for runtime traffic; use the direct connection for migrations.
- **Prisma says `DIRECT_URL` is missing:** confirm it is present in local `.env`
  and as a GitHub Secret.
- **Workflow says no migrations found:** generate the first migration locally
  with `npx prisma migrate dev --name init`, commit `prisma/migrations/`, and
  push it.
- **Port 8000 is busy:** use `php artisan serve --port=8001` and update
  `APP_URL`.
