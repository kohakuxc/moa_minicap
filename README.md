<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# MOA optical clinic

MOA is a Laravel 12 patient-management and eyewear application backed by
PostgreSQL/Supabase. Laravel is the application source of truth for users,
appointments, prescriptions, roles, authorization, and sessions. The Prisma
schema remains available for validation and migration tooling, but application
requests use Eloquent and Laravel migrations so there is no split runtime data
model.

## Local setup

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Required server-side environment values include `APP_KEY`, `APP_URL`,
`APP_DEBUG`, `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`, `DATABASE_URL`, and `DIRECT_URL`. Keep `.env`
local and never expose either database URL to browser code.

## Mail and account security

Configure a real HTTPS application URL and a real mail provider in production.
Set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`. Local development
can use `MAIL_MAILER=log`; verification and password-reset links will appear
in the Laravel log.

Registration sends email verification. Patient records require a verified
email. Password-reset requests always return a generic response so they do
not reveal whether an email is registered. Login, registration, verification,
and reset endpoints are rate-limited.

Promote an administrator from a trusted terminal only:

```powershell
php artisan user:promote patient@example.com
```

The command prompts for confirmation. Never accept a role value from a
browser request.

## Patient and admin areas

- `/dashboard` is restricted to the authenticated, verified patient.
- `/admin` requires an authenticated, verified user with the `admin` role.
- Patients can update only their own profile and cancel their own future
  appointments.
- Admin actions are validated and protected by the admin middleware.

## Database and privacy

Run `php artisan migrate` for Laravel's users, sessions, appointments,
prescriptions, and audit-log tables. Laravel migrations are the only runtime
schema migration path; Prisma is validation-only in this repository. Use fake
records during development. Production use of
patient or prescription data requires a legal, privacy, security, retention,
regional, and healthcare-compliance review appropriate to your jurisdiction.
Enable Supabase backups and test restoration before relying on the system.
Use `APP_DEBUG=false`, HTTPS, secure cookies, a secret manager, and least
privilege in production. Never commit `.env`, passwords, service-role keys,
database dumps, or real patient data.

## Verification

```powershell
php artisan test
php artisan route:list
npx prisma validate
npm run build
```

## Original Laravel reference

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
