# GoArena Deployment Guide

## Requirements

- PHP 8.4+
- PostgreSQL 16+
- Node.js 20+
- Nginx
- KataGo (for AI analysis)

## Server Setup

### 1. Create Directory Structure
```bash
sudo mkdir -p /var/www/goarena/{prod,dev}
sudo chown -R www-data:www-data /var/www/goarena
```

### 2. Clone Repository
```bash
cd /var/www/goarena/prod
sudo -u www-data git clone git@github.com:narbonnais/GoArena.git .
```

### 3. PostgreSQL Database
```bash
sudo -u postgres createdb goarena
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE goarena TO your_user;"
sudo -u postgres psql -d goarena -c "GRANT ALL ON SCHEMA public TO your_user;"
sudo -u postgres psql -c "ALTER DATABASE goarena OWNER TO your_user;"
```

### 4. Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` with:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=goarena
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
```

### 5. Install Dependencies
```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build
```

### 6. Laravel Setup
```bash
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --class=TimeControlSeeder
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan optimize
```

### 7. Nginx Configuration
```nginx
server {
    server_name your-domain.com;
    root /var/www/goarena/prod/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_pass http://127.0.0.1:8082;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 60s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 8. SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com
```

## KataGo Setup (AI Analysis)

### 1. Install KataGo Binary
```bash
cd /tmp
curl -L -o katago.zip "https://github.com/lightvector/KataGo/releases/download/v1.16.4/katago-v1.16.4-eigenavx2-linux-x64.zip"
unzip katago.zip
sudo cp katago /usr/local/bin/
sudo chmod +x /usr/local/bin/katago
```

### 2. Download Human SL Model
```bash
sudo mkdir -p /var/www/goarena/prod/storage/katago
cd /var/www/goarena/prod/storage/katago
sudo curl -L -o b18c384nbt-humanv0.bin.gz \
    "https://github.com/lightvector/KataGo/releases/download/v1.15.0/b18c384nbt-humanv0.bin.gz"
sudo chown -R www-data:www-data /var/www/goarena/prod/storage/katago
```

### 3. Create GTP Config
Create `/var/www/goarena/prod/storage/katago/gtp.cfg`:
```ini
logDir = /var/www/goarena/prod/storage/logs/katago
logAllGTPCommunication = false
logSearchInfo = false
logToStderr = false
rules = japanese
allowResignation = true
resignThreshold = -0.95
resignConsecTurns = 5
maxVisits = 100
numSearchThreads = 2
humanSLProfile = preaz_9d
```

### 4. Create Log Directory
```bash
sudo mkdir -p /var/www/goarena/prod/storage/logs/katago
sudo chmod 777 /var/www/goarena/prod/storage/logs/katago
```

## Reverb WebSocket Server (Optional)

### 1. Create Systemd Service
Create `/etc/systemd/system/goarena-reverb.service`:
```ini
[Unit]
Description=GoArena Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/goarena/prod
ExecStart=/usr/bin/php8.4 artisan reverb:start --host=127.0.0.1 --port=8082
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 2. Enable and Start
```bash
sudo systemctl daemon-reload
sudo systemctl enable goarena-reverb
sudo systemctl start goarena-reverb
```

## Deployment Script

For updates, run:
```bash
cd /var/www/goarena/prod
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize
sudo systemctl restart php8.4-fpm
```

## Troubleshooting

### KataGo 500 Errors
If KataGo returns 500 errors, ensure `proc_open()` passes environment variables. PHP-FPM's `clear_env` setting (default: yes) clears env vars for child processes.

### CSRF 419 Errors
Clear browser cookies and Laravel session cache:
```bash
php artisan cache:clear
rm -rf storage/framework/sessions/*
```

### Large Header Errors
Add fastcgi buffer settings to Nginx (see config above).
