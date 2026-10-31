# Production deployment (issue #41)

> **Status: prepared, not yet deployed.** A server, a domain and a certificate cannot be created from the code repository. Everything that can be written down and tested without them is here; the checklist at the bottom is what is left for a person with server access.

## 1. Target (ADR 0002)

One Linux VPS: Nginx, PHP-FPM 8.3 (with `bcmath`, `gd`, `intl`, `mbstring`, `pdo_mysql`, `zip`), MySQL 8, Node 20 (only to build assets), Supervisor, cron, Let's Encrypt certificate.

## 2. One-time server setup

```bash
sudo apt install nginx mysql-server php8.3-fpm php8.3-{bcmath,gd,intl,mbstring,mysql,xml,zip,curl} supervisor git unzip nodejs npm composer
sudo mysql -e "CREATE DATABASE ahla CHARACTER SET utf8mb4; CREATE USER 'ahla'@'localhost' IDENTIFIED BY '<long random password>'; GRANT ALL ON ahla.* TO 'ahla'@'localhost';"
sudo git clone https://github.com/nourbouzir/Ahla.git /var/www/ahla && sudo chown -R www-data:www-data /var/www/ahla
sudo cp deploy/nginx.conf /etc/nginx/sites-available/ahla && sudo ln -s /etc/nginx/sites-available/ahla /etc/nginx/sites-enabled/ && sudo certbot --nginx -d ahla.example.com
sudo cp deploy/ahla-worker.conf /etc/supervisor/conf.d/ && sudo supervisorctl reread && sudo supervisorctl update
sudo cp deploy/crontab /etc/cron.d/ahla
```

## 3. The server `.env` (the only place secrets live)

Start from `.env.example` and set at least:

| Key | Value |
|-----|-------|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` (the deploy script refuses anything else) |
| `APP_KEY` | `php artisan key:generate --force` once, then never change it (it also protects the stored ID documents) |
| `APP_URL` | `https://ahla.example.com` |
| `DB_*` | the MySQL user above |
| `SESSION_SECURE_COOKIE` | `true` |
| `TRUSTED_PROXIES` | `*` only if a load balancer sits in front, otherwise empty |
| `QUEUE_CONNECTION` | `database` |
| `MAIL_*` | the real mail provider; `MAIL_FROM_ADDRESS` |
| `AHLA_ADMIN_PASSWORD` | the first admin's password (otherwise a random one is printed once by the seeder) |
| `AHLA_PAYMENT_GATEWAY` | leave empty for the sandbox (ADR 0005) until the ClicToPay adapter exists |
| `AHLA_PAYMENT_WEBHOOK_SECRET` | long random string shared with the provider |
| `AHLA_TRANSLATE_URL` | the translation service (ADR 0006), empty = originals only |

Then: `php artisan migrate --force && php artisan db:seed --class='App\Modules\Auth\Database\Seeders\AuthDatabaseSeeder' --force` (creates only the admin; demo data is never seeded in production).

## 4. Every deploy

`scripts/deploy.sh` (as `www-data`): checks the environment, takes a backup, `artisan down`, pulls `origin/main`, installs without dev packages, builds assets, migrates, caches config/routes/views, restarts the workers, `artisan up`, and runs `scripts/smoke-test.sh`.

## 5. Checks after a deploy

- `scripts/smoke-test.sh https://ahla.example.com` (home, search, login, API, guests redirected, `/.env` not served, HSTS header present).
- `supervisorctl status` shows two `ahla-worker` processes RUNNING.
- `php artisan schedule:list` lists the four jobs, and `grep schedule /var/log/syslog` shows cron firing every minute.
- The main journey from `docs/demo.md` works on the server (use a throwaway tourist and host).
- Run the k6 load test and add the numbers to `docs/performance.md`.

## 6. Checklist for the person with server access (not done yet)

- [ ] Server, domain and DNS
- [ ] Certificate installed, HTTP redirects to HTTPS
- [ ] `.env` filled in, `APP_KEY` stored in the team password manager
- [ ] First `scripts/deploy.sh` green
- [ ] Worker and scheduler verified running
- [ ] Off-site backup copy configured, restore drill repeated on the server (`docs/runbook.md`)
- [ ] Main journey demonstrated on the live URL
