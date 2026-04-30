<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# PortoHub

PortoHub adalah aplikasi web berbasis Laravel 13, Livewire, dan Vite.

## Prasyarat

- PHP 8.3+
- Composer
- Node.js (LTS) dan npm
- Database: MySQL/MariaDB atau SQLite

## Cara Clone Sampai Jalan (Development)

1) Clone repo dan masuk ke folder proyek

```bash
git clone https://github.com/ZalSanDiv-PMPL/PortoHub.git
cd PortoHub
```

2) Install dependency PHP

```bash
composer install
```

3) Salin file environment dan generate app key

```bash
copy .env.example .env
php artisan key:generate
```

4) Konfigurasi database di file `.env`

Contoh MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portohub
DB_USERNAME=root
DB_PASSWORD=
```

5) Jalankan migrasi

```bash
php artisan migrate
```

6) Install dependency frontend

```bash
npm install
```

7) Jalankan aplikasi (dua terminal)

Terminal A:

```bash
php artisan serve
```

Terminal B:

```bash
npm run dev
```

Buka aplikasi di http://127.0.0.1:8000

### Alternatif cepat (semua sekaligus)

```bash
composer run dev
```

### Build untuk production

```bash
npm run build
```

## Git Workflow (Branch)

### Membuat branch baru

```bash
git checkout main
git pull origin main
git checkout -b feat/nama-fitur
```

### Push branch ke remote

```bash
git push -u origin feat/nama-fitur
```

### Update branch dari main

```bash
git fetch origin
git rebase origin/main
```

### Saran penamaan branch

- feat/nama-fitur
- fix/perbaikan-bug
- chore/maintenance

## Testing

```bash
php artisan test
```

## About Laravel

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
