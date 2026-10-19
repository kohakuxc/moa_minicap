# Oculo setup guide for Windows

This guide assumes a completely new Windows computer and uses **Supabase as
the hosted PostgreSQL database**.

## Is Supabase good for Oculo?

Yes. Supabase is a good fit because it provides managed PostgreSQL, backups,
database logs, a SQL editor, and a helpful dashboard. Laravel connects to it
using ordinary PostgreSQL credentials, and the existing Prisma provider remains
`postgresql`. No database rewrite is needed.

For the first version, use Supabase as the database and backup service while
Laravel remains responsible for authentication, authorization, validation, and
business rules. You do not need the Supabase JavaScript client just to use its
PostgreSQL database.

For real patient or prescription data, do not use a free/test project without
first reviewing Supabase's current security, retention, region, and healthcare
compliance terms for your country. Use a paid plan and confirm that your
hosting/database setup meets applicable privacy requirements.

Other good hosted PostgreSQL providers include Neon, Railway, Render, and
Amazon RDS. Supabase is the easiest recommendation for this project because it
has a beginner-friendly dashboard and remains standard PostgreSQL.

## 1. Install the required tools

Install these applications:

1. **Git for Windows** — https://git-scm.com/download/win
2. **Laravel Herd for Windows** — https://herd.laravel.com/windows
   - Herd includes PHP and a local Laravel development environment.
   - Alternatively install PHP 8.2+ and Composer separately.
3. **Node.js LTS** — https://nodejs.org/en/download
   - Required for Prisma CLI and future frontend builds.
4. **ngrok** — https://ngrok.com/download/windows
5. **Supabase account** — https://supabase.com/

You do not need to install PostgreSQL locally when using Supabase. Close and
reopen PowerShell after installing the tools, then verify:

```powershell
php --version
composer --version
node --version
npm --version
git --version
ngrok version
```

## 2. Create the hosted database

In Supabase:

1. Create a new project.
2. Choose a region close to your users.
3. Save the database password in a password manager.
4. Open **Project Settings > Database > Connect**.
5. Copy the direct/session connection details.

The direct connection is preferred for Prisma migrations. The session pooler
connection is useful when a hosting provider cannot reach Supabase's direct
database host. Never expose either connection string in browser JavaScript.

## 3. Configure Laravel

Open PowerShell in the project directory:

```powershell
cd D:\MOASystem
Copy-Item .env.example .env
composer install
php artisan key:generate
```

Edit `.env`. Replace the placeholders with the values from Supabase:

```dotenv
APP_NAME=Oculo
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=your-supabase-db-host
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-database-password

DATABASE_URL="postgresql://postgres:your-supabase-database-password@your-supabase-db-host:5432/postgres?schema=public"
```

If you use the Supabase session pooler, use its host, port, and username from
the Connect panel for the `DB_*` values. For Prisma migrations, keep a direct
connection in `DIRECT_URL` when Supabase provides one:

```dotenv
DIRECT_URL="postgresql://postgres:your-supabase-database-password@your-supabase-db-host:5432/postgres?schema=public"
```

If `DIRECT_URL` is present, add `directUrl = env("DIRECT_URL")` to the
PostgreSQL datasource in `prisma/schema.prisma`.

The `DB_*` values are used by Laravel. `DATABASE_URL` is used by Prisma. Keep
both pointed at the same Supabase project.

## Connect GitHub Actions to Supabase

The repository must not contain a Supabase password or connection string.
Instead, add these encrypted repository secrets on GitHub:

1. Open `https://github.com/kohakuxc/moa_minicap`.
2. Go to **Settings > Secrets and variables > Actions**.
3. Select **New repository secret**.
4. Add `SUPABASE_DATABASE_URL` with the Supabase pooler/session connection
   string, or the direct connection if your deployment supports it.
5. Add `SUPABASE_DIRECT_URL` with the Supabase direct connection string used
   for migrations.

The workflow at `.github/workflows/supabase.yml` will validate the Prisma
schema and apply committed migrations whenever Prisma files are pushed to
`main`. It can also be started manually from the repository's **Actions** tab.
Do not use the Supabase service-role key for Prisma migrations; these secrets
are PostgreSQL connection strings and must remain server-side.

## 4. Create the schema

```powershell
npm install
npx prisma generate
npx prisma validate
npx prisma migrate dev --name init
```

The migration creates the patient, appointment, and prescription tables in
Supabase. You can inspect them in Supabase's **Table Editor**.

## 5. Start the application

Use one PowerShell window:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000 in your browser. If you later use the Vite
development workflow, use a second window:

```powershell
npm run dev
```

## 6. Verify the setup

From a second PowerShell window:

```powershell
php artisan about
php artisan route:list
php artisan migrate:status
Invoke-WebRequest http://127.0.0.1:8000
```

Common fixes:

- `php is not recognized`: reopen PowerShell or use Laravel Herd.
- `could not find driver`: enable the PHP `pdo_pgsql` extension.
- `password authentication failed`: re-copy the Supabase database password.
- Prisma `P1001`: check the host, port, and whether your network blocks the
  direct connection; try the Supabase session pooler.
- Port 8000 is busy: run `php artisan serve --port=8001` and update `APP_URL`.

## 7. Test on a phone with ngrok

Start Laravel first, then open another PowerShell window:

```powershell
ngrok config add-authtoken YOUR_NGROK_TOKEN
ngrok http 8000
```

Open the HTTPS forwarding URL shown by ngrok on your phone. Never commit an
ngrok token or `.env` file.

## 8. Local PostgreSQL alternative

If you later want an offline development database, install PostgreSQL and
pgAdmin from https://www.postgresql.org/download/windows/. The application
still works; only the `.env` connection values change to `127.0.0.1`, port
`5432`, and your local database credentials.
