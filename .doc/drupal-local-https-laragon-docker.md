# Drupal Local HTTPS — Laragon Reverse Proxy + Docker

## Mục tiêu

Giữ Drupal chạy hoàn toàn trong Docker, Laragon chỉ xử lý HTTPS:

```text
Browser
   │
   │ https://drupal.local
   ▼
Laragon Apache :443
   │  TLS termination
   │  reverse proxy
   ▼
http://127.0.0.1:8080
   │
   ▼
Docker Drupal
Apache + PHP
```

URL dùng chính:

```text
https://drupal.local
```

URL debug trực tiếp Docker:

```text
http://drupal.local:8080
```

---

## 1. Docker giữ nguyên

Drupal container vẫn expose:

```yaml
ports:
  - "8080:80"
```

Kiểm tra:

```bash
cd ~/projects/drupal-local

docker compose up -d
docker compose ps
```

Test từ Windows:

```cmd
curl.exe -I http://127.0.0.1:8080
```

`200`, `301` hoặc `302` đều cho thấy backend Drupal đang reachable.

---

## 2. Khai báo domain local

Sửa file Windows:

```text
C:\Windows\System32\drivers\etc\hosts
```

Thêm:

```text
127.0.0.1 drupal.local
```

Flush DNS:

```cmd
ipconfig /flushdns
```

Kiểm tra:

```cmd
ping drupal.local
```

Phải resolve về:

```text
127.0.0.1
```

---

## 3. Cài `mkcert`

Kiểm tra:

```cmd
mkcert -version
```

Nếu chưa có và đang dùng Chocolatey:

```cmd
choco install mkcert
```

Cài Local CA vào Windows trust store:

```cmd
mkcert -install
```

Kiểm tra CA root:

```cmd
mkcert -CAROOT
```

Có thể kiểm tra Windows trust store bằng:

```cmd
certmgr.msc
```

Vào:

```text
Trusted Root Certification Authorities
→ Certificates
```

Tìm CA của `mkcert`.

> Không commit hoặc chia sẻ `rootCA-key.pem`.

---

## 4. Tạo certificate cho `drupal.local`

```cmd
mkdir C:\laragon\etc\ssl\drupal.local
cd C:\laragon\etc\ssl\drupal.local
```

Tạo certificate:

```cmd
mkcert -cert-file drupal.local.pem -key-file drupal.local-key.pem drupal.local
```

Kết quả:

```text
C:\laragon\etc\ssl\drupal.local\
├── drupal.local.pem
└── drupal.local-key.pem
```

Apache dùng trực tiếp `.pem`, không cần đổi sang `.cer`.

Kiểm tra certificate:

```cmd
certutil -dump C:\laragon\etc\ssl\drupal.local\drupal.local.pem
```

Cần thấy SAN chứa:

```text
DNS Name=drupal.local
```

---

## 5. Kiểm tra module Apache Laragon

```cmd
httpd -M | findstr /I "ssl proxy proxy_http headers"
```

Cần có:

```text
ssl_module
proxy_module
proxy_http_module
headers_module
```

Nếu thiếu, kiểm tra `httpd.conf`:

```apache
LoadModule ssl_module modules/mod_ssl.so
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_http_module modules/mod_proxy_http.so
LoadModule headers_module modules/mod_headers.so
```

---

## 6. Tạo VirtualHost HTTPS

File:

```text
C:\laragon\etc\apache2\sites-enabled\drupal.local.conf
```

Nội dung:

```apache
<VirtualHost *:80>
    ServerName drupal.local

    Redirect permanent / https://drupal.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName drupal.local

    SSLEngine on

    SSLCertificateFile "C:/laragon/etc/ssl/drupal.local/drupal.local.pem"
    SSLCertificateKeyFile "C:/laragon/etc/ssl/drupal.local/drupal.local-key.pem"

    ProxyRequests Off
    ProxyPreserveHost On

    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-Port "443"

    ProxyPass "/" "http://127.0.0.1:8080/"
    ProxyPassReverse "/" "http://127.0.0.1:8080/"
</VirtualHost>
```

Không để một vhost khác có cùng:

```apache
ServerName drupal.local
```

---

## 7. Validate Apache

```cmd
httpd -t
```

Mong đợi:

```text
Syntax OK
```

Kiểm tra vhost:

```cmd
httpd -S
```

Cần thấy:

```text
*:80  drupal.local
*:443 drupal.local
```

Kiểm tra port 443:

```cmd
netstat -ano | findstr :443
```

Sau đó restart Apache trong Laragon.

---

## 8. Cấu hình Drupal cho reverse proxy

Trong:

```text
src/web/sites/default/settings.php
```

thêm cuối file:

```php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

Tạo:

```text
src/web/sites/default/settings.local.php
```

Nội dung:

```php
<?php

use Symfony\Component\HttpFoundation\Request;

$settings['trusted_host_patterns'] = [
  '^drupal\.local$',
  '^localhost$',
];

if (
  isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
  && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
  && isset($_SERVER['REMOTE_ADDR'])
) {
  $settings['reverse_proxy'] = TRUE;

  // Chỉ phù hợp cho local development.
  $settings['reverse_proxy_addresses'] = [
    $_SERVER['REMOTE_ADDR'],
  ];

  $settings['reverse_proxy_trusted_headers'] =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_PORT;
}
```

Không commit local config:

```gitignore
/web/sites/default/settings.local.php
```

Rebuild cache:

```bash
docker compose exec drupal vendor/bin/drush cr
```

---

## 9. Test

HTTP phải redirect sang HTTPS:

```cmd
curl.exe -I http://drupal.local
```

Mong đợi:

```text
HTTP/1.1 301 Moved Permanently
Location: https://drupal.local/
```

Test HTTPS:

```cmd
curl.exe -I https://drupal.local
```

Nếu Windows `curl.exe` báo:

```text
CRYPT_E_NO_REVOCATION_CHECK
```

do Schannel không kiểm tra được revocation của local certificate, test bằng:

```cmd
curl.exe --ssl-revoke-best-effort -I https://drupal.local
```

Nếu vẫn cần test local:

```cmd
curl.exe --ssl-no-revoke -I https://drupal.local
```

Không dùng `-k` để xác nhận certificate trust, vì `-k` bỏ qua certificate verification.

---

## 10. Kiểm tra browser

Mở:

```text
https://drupal.local
```

Kết quả đúng:

- Không có `Your connection is not private`
- Certificate cấp cho `drupal.local`
- Request được proxy tới Drupal Docker

Kiểm tra Docker log:

```bash
docker compose logs -f drupal
```

Refresh:

```text
https://drupal.local
```

Nếu log xuất hiện request `GET /`, luồng đã đúng:

```text
Browser
→ HTTPS
→ Laragon Apache
→ HTTP :8080
→ Docker
→ Drupal
```

---

## Checklist

```text
[ ] Docker Drupal chạy ở :8080
[ ] hosts có 127.0.0.1 drupal.local
[ ] mkcert đã cài
[ ] mkcert -install thành công
[ ] drupal.local.pem tồn tại
[ ] drupal.local-key.pem tồn tại
[ ] SAN có drupal.local
[ ] Apache có ssl/proxy/proxy_http/headers
[ ] drupal.local có vhost :80 và :443
[ ] http://drupal.local redirect HTTPS
[ ] https://drupal.local không báo certificate warning
[ ] Laragon proxy tới 127.0.0.1:8080
[ ] Drupal cấu hình reverse proxy
[ ] drush cr đã chạy
```

## Kiến trúc cần nhớ

```text
Browser
   ↓ HTTPS
Laragon / Reverse Proxy
   ↓ HTTP nội bộ
Drupal Docker
```

Laragon chỉ terminate TLS; PHP/Apache xử lý Drupal vẫn là runtime trong Docker. Đây là mô hình phù hợp để làm quen với cách deploy Drupal sau reverse proxy ở môi trường thực tế.
