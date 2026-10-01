# Drupal Local với Docker + WSL2

## 1. Mục tiêu

Môi trường local hiện tại:

```text
Windows 10
├── Laragon                 -> vẫn chạy các project cũ trên port 80
├── Docker Desktop          -> dùng WSL2 backend
└── Ubuntu 24.04 (WSL2)
    └── ~/projects/drupal-local
        ├── Dockerfile
        ├── docker-compose.yml
        └── src/            -> source Drupal
```

Drupal chạy trong Docker:

```text
Browser
  ↓
http://drupal.local:8080
  ↓
Docker: 8080 -> 80
  ↓
Apache + PHP 8.4
  ↓
Drupal /var/www/html/web
  ↓
MariaDB service: db:3306
```

> Dùng port `8080` để không xung đột với Laragon đang dùng port `80`.

---

## 2. Vị trí project

Không đặt project Drupal trong:

```text
C:\...
D:\...
/mnt/c/...
/mnt/d/...
```

Nên đặt trong filesystem Linux của WSL:

```bash
/home/root_wsl/projects/drupal-local
```

Tạo project folder:

```bash
mkdir -p ~/projects/drupal-local
cd ~/projects/drupal-local
mkdir src
```

Kiểm tra:

```bash
pwd
```

Kết quả mong đợi:

```text
/home/root_wsl/projects/drupal-local
```

---

## 3. Dockerfile

File:

```text
~/projects/drupal-local/Dockerfile
```

```dockerfile
FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    gd \
    intl \
    mbstring \
    curl \
    zip \
    opcache

RUN a2enmod rewrite headers expires

ENV APACHE_DOCUMENT_ROOT=/var/www/html/web

RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf

RUN printf '<Directory /var/www/html/web>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/drupal.conf \
    && a2enconf drupal

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

---

## 4. docker-compose.yml

File:

```text
~/projects/drupal-local/docker-compose.yml
```

```yaml
services:
  drupal:
    build:
      context: .
      dockerfile: Dockerfile

    container_name: drupal_app

    ports:
      - "8080:80"

    volumes:
      - ./src:/var/www/html

    depends_on:
      db:
        condition: service_healthy

    environment:
      COMPOSER_MEMORY_LIMIT: -1

  db:
    image: mariadb:11.4

    container_name: drupal_db

    ports:
      - "3307:3306"

    environment:
      MARIADB_DATABASE: drupal
      MARIADB_USER: drupal
      MARIADB_PASSWORD: drupal
      MARIADB_ROOT_PASSWORD: root

    volumes:
      - db_data:/var/lib/mysql

    healthcheck:
      test:
        - CMD
        - healthcheck.sh
        - --connect
        - --innodb_initialized
      interval: 5s
      timeout: 5s
      retries: 10

volumes:
  db_data:
```

---

## 5. Build Docker image

```bash
cd ~/projects/drupal-local
docker compose build
```

Kiểm tra Docker:

```bash
docker --version
docker compose version
```

---

## 6. Cài Drupal bằng Composer

Chạy khi `src/` đang trống:

```bash
docker compose run --rm --no-deps drupal \
  composer create-project drupal/recommended-project:^11 .
```

Sau đó source có dạng:

```text
src/
├── composer.json
├── composer.lock
├── vendor/
└── web/
    ├── core/
    ├── modules/
    ├── profiles/
    ├── sites/
    ├── themes/
    └── index.php
```

---

## 7. Start container

```bash
docker compose up -d
```

Kiểm tra:

```bash
docker compose ps
```

Xem log Drupal/Apache:

```bash
docker compose logs -f drupal
```

Xem log MariaDB:

```bash
docker compose logs -f db
```

---

## 8. Domain local

Windows hosts file:

```text
C:\Windows\System32\drivers\etc\hosts
```

Thêm:

```text
127.0.0.1 drupal.local
```

Truy cập:

```text
http://drupal.local:8080
```

Do Laragon vẫn dùng port `80`, Docker dùng `8080` nên hai môi trường có thể chạy cùng lúc.

---

## 9. Chuẩn bị file trước khi Drupal install

Drupal installer cần:

```text
web/sites/default/settings.php
web/sites/default/files/
```

Tạo:

```bash
cp src/web/sites/default/default.settings.php \
   src/web/sites/default/settings.php

mkdir -p src/web/sites/default/files
```

Cho installer quyền ghi tạm thời:

```bash
chmod 666 src/web/sites/default/settings.php
chmod 777 src/web/sites/default/files
```

> Chỉ dùng quyền rộng này tạm thời trong quá trình install local. Không dùng `chmod -R 777` cho toàn project.

---

## 10. Cấu hình database trong Drupal installer

Dùng:

```text
Database name: drupal
Username:      drupal
Password:      drupal
Host:          db
Port:          3306
```

Không dùng:

```text
localhost
127.0.0.1
3307
```

Lý do:

```text
Drupal container
   ↓ db:3306
Docker network
   ↓
MariaDB container
```

`3307` chỉ dùng khi kết nối từ Windows/DataGrip:

```text
Host: localhost
Port: 3307
Database: drupal
User: drupal
Password: drupal
```

---

## 11. Permission sau khi install

Khóa lại file cấu hình:

```bash
chmod 755 src/web/sites/default
chmod 644 src/web/sites/default/settings.php
```

Cho Apache/PHP trong container sở hữu thư mục runtime:

```bash
docker compose exec -u root drupal \
  chown -R www-data:www-data /var/www/html/web/sites/default/files
```

Directory nên là `775`:

```bash
docker compose exec -u root drupal \
  find /var/www/html/web/sites/default/files \
  -type d -exec chmod 775 {} \;
```

File nên là `664`:

```bash
docker compose exec -u root drupal \
  find /var/www/html/web/sites/default/files \
  -type f -exec chmod 664 {} \;
```

---

## 12. Cài Drush

```bash
docker compose exec drupal composer require drush/drush
```

Kiểm tra:

```bash
docker compose exec drupal vendor/bin/drush status
```

Clear cache:

```bash
docker compose exec drupal vendor/bin/drush cr
```

---

## 13. Các lệnh sử dụng hằng ngày

Start:

```bash
cd ~/projects/drupal-local
docker compose up -d
```

Stop:

```bash
docker compose down
```

Xem trạng thái:

```bash
docker compose ps
```

Vào container Drupal:

```bash
docker compose exec drupal bash
```

Composer:

```bash
docker compose exec drupal composer install
```

Cài contributed module:

```bash
docker compose exec drupal composer require drupal/admin_toolbar
```

Enable module:

```bash
docker compose exec drupal vendor/bin/drush en admin_toolbar
```

Rebuild cache:

```bash
docker compose exec drupal vendor/bin/drush cr
```

---

## 14. Sau khi restart Windows có cần setup lại không?

Không.

Các thứ vẫn được giữ:

- source Drupal trong WSL;
- permission của source;
- owner của `sites/default/files`;
- MariaDB data trong named volume `db_data`.

Sau khi mở máy chỉ cần Docker Desktop chạy, sau đó:

```bash
cd ~/projects/drupal-local
docker compose up -d
```

Nếu container đã tự start thì thậm chí không cần lệnh này.

---

## 15. Ghi nhớ

```text
Source Drupal:       ~/projects/drupal-local/src
Drupal document root: /var/www/html/web
Drupal URL:          http://drupal.local:8080
Drupal DB host:      db:3306
Windows DB access:   localhost:3307
Drupal writable dir: web/sites/default/files
Drupal config file:  web/sites/default/settings.php
```
