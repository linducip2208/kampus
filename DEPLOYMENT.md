# Deployment checklist

1. Siapkan PHP 8.4+, MySQL 8.4+, Redis, Nginx, Supervisor, dan storage object/local.
2. Deploy source ke server, jalankan `composer install --no-dev --optimize-autoloader`.
3. Salin `.env.example` ke `.env`, isi APP_KEY, APP_URL, DB, Redis, mail, storage, dan INDEXNOW_KEY.
4. Jalankan `php artisan migrate --force`, `php artisan storage:link`, `npm ci && npm run build`.
5. Cache config/route/view dan jalankan queue worker + scheduler via Supervisor.
6. Pastikan `/up`, `/robots.txt`, `/sitemap.xml`, `/docs`, `/login`, dan `/admin` dapat diakses.
7. Konfigurasikan backup database + uploads, retention, monitoring failed jobs, dan disaster recovery.

Nginx document root harus menunjuk ke folder `public`. Jangan menyimpan credential di repository.
