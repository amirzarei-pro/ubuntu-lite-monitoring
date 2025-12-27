# Ubuntu Lite Monitoring

A lightweight PHP-based dashboard to visualize system metrics: CPU, memory, disk usage, uptime and OS info, top processes, recent SSH logins, and Docker status (when available).

## Features
- CPU load, model and core count
- RAM totals, usage, available, cached and swap usage
- Disk total/used/free and percentage
- System info: hostname, uptime, kernel, OS description
- Top processes by memory usage
- Recent SSH logins
- Docker daemon/version and container list

## Project Structure
```
.
├── index.php              # Entry point and UI
├── src/
│   └── System.php         # Modularized metrics functions
└── (no Docker assets)     # Docker removed by request
```

## Requirements (Ubuntu server)
- Nginx
- PHP 8.3 (FPM + CLI) and common extensions
- System tools used by the dashboard metrics: `procps`, `iproute2`, `lsb-release`, `coreutils`, `util-linux`, `curl`

Install on Ubuntu 22.04/24.04:

```bash
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-cli php8.3-common php8.3-curl php8.3-xml php8.3-zip php8.3-mbstring \
    procps iproute2 lsb-release coreutils util-linux curl
```

## Deploy on Nginx + PHP-FPM
1) Clone the app:
```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/amirzarei-pro/ubuntu-lite-monitoring.git
sudo chown -R www-data:www-data ubuntu-lite-monitoring
```

2) Create an Nginx server block (e.g., `/etc/nginx/sites-available/ubuntu-lite-monitoring`):
```nginx
server {
    listen 80;
    server_name _;

    root /var/www/ubuntu-lite-monitoring;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/ubuntu-lite-monitoring /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Ensure PHP-FPM is running:
```bash
sudo systemctl enable --now php8.3-fpm
```

3) Browse to your server IP (http://<server-ip>) to view the dashboard.

## Local Dev (PHP built-in server)
If you prefer not to configure Nginx locally and have PHP 8.3 installed:
```bash
php -S 127.0.0.1:8000
```
Open http://127.0.0.1:8000 in your browser.

## API Endpoint
The UI fetches fresh data via `/?api=data` returning JSON. You can integrate or extend it by consuming this endpoint.

## Development Notes
- Core logic is modularized in `src/System.php`.
- `index.php` focuses on routing and rendering.
- For more structure, you can further split UI into templates or adopt a framework, but this code intentionally remains simple.

## License
Proprietary or internal use (no explicit license). Adjust as needed.
