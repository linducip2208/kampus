# Campus ERP

Enterprise University Management System / SIAKAD Universitas berbasis Laravel 13. Aplikasi ini menyatukan struktur organisasi, siklus akademik, portal mahasiswa, keuangan mahasiswa, audit, laporan, dan API v1 dalam modular monolith.

## Requirements

- PHP 8.3 local / 8.4 production
- Composer 2+
- MySQL 8.4+ untuk production
- Redis untuk queue/cache production
- Node 20+ untuk asset build

## Instalasi

```bash
composer install
copy .env.example .env
php artisan key:generate
# isi DB_* dengan MySQL
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Untuk demo lokal tanpa MySQL, gunakan `DB_CONNECTION=sqlite` dan file `database/database.sqlite`.

## Akun demo

Semua password: `password`.

| Peran | Email |
|---|---|
| Super Admin | admin@kampus.test |
| Rektor | rektor@kampus.test |
| BAAK | baak@kampus.test |
| Finance | finance@kampus.test |
| Dosen Wali | dosen@kampus.test |
| Mahasiswa | mahasiswa@kampus.test |

Login branded: `/login`. Admin Filament: `/admin`.

## Verifikasi

```bash
php artisan test
php artisan route:list
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan seo:indexnow
php artisan backup:database
```

Queue worker: `php artisan queue:work`. Scheduler: `php artisan schedule:work` atau cron production.

## Struktur dokumentasi

- `ARCHITECTURE.md` — batas modul dan request flow.
- `ERD.md` — relasi core.
- `DATABASE.md` — conventions dan integritas data.
- `PERMISSIONS.md` — role/scope matrix.
- `MODULES.md` — implemented/incomplete.
- `DEVELOPMENT_PLAN.md` — roadmap fase.
- `/docs` — dokumentasi web + akun demo + tutorial alur.

## SEO

Sitemap dinamis ada di `/sitemap.xml`, robots di `/robots.txt`, blog RSS di `/blog/feed.xml`. Setelah deployment, submit `https://domain-anda/sitemap.xml` ke Google Search Console dan isi `INDEXNOW_KEY` di `.env`.

## Deployment

Gunakan MySQL, Redis, queue worker, scheduler, HTTPS, storage S3-compatible atau local fallback. Salin `.env.example`, gunakan `php artisan config:cache`, `route:cache`, `view:cache`, dan jadwalkan `backup:database`. Detail production ditulis di `DEPLOYMENT.md`.
