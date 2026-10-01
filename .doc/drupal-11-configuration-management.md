# Drupal 11 Configuration Management (Docker)

## 1. Mục tiêu

Drupal lưu **active configuration** trong database.  
Để đưa cấu hình lên Git và đồng bộ giữa các môi trường, export chúng thành YAML trong `config_sync_directory`.

Flow chuẩn:

```text
Admin UI
  ↓
Active config (DB)
  ↓
drush cex
  ↓
YAML files
  ↓
Git
  ↓
drush cim
  ↓
Active config (DB)
```

---

## 2. Khuyến nghị cấu hình thư mục sync

Mặc định Drupal có thể tạo thư mục dạng:

```text
sites/default/files/config_<random-hash>/sync
```

Cho project dùng Git, nên đổi thành một path cố định, ví dụ:

```php
$settings['config_sync_directory'] = '../config/sync';
```

Giả sử Drupal document root là:

```text
project/
├── config/
│   └── sync/
└── web/
    └── sites/default/settings.php
```

> Nên đặt config ngoài public webroot nếu cấu trúc project cho phép.

Tạo thư mục nếu chưa có:

```bash
mkdir -p config/sync
```

---

## 3. Export configuration

Nếu service PHP/Drupal trong `docker-compose.yml` tên là `drupal`:

```bash
docker compose exec drupal ./vendor/bin/drush cex -y
```

Kiểm tra trạng thái:

```bash
docker compose exec drupal ./vendor/bin/drush config:status
```

Sau export:

```bash
git status
git diff
```

Commit các YAML cần thiết lên Git.

---

## 4. Import configuration

Sau khi pull code/config từ Git:

```bash
docker compose exec drupal ./vendor/bin/drush cim -y
docker compose exec drupal ./vendor/bin/drush cr
```

Nếu có database updates:

```bash
docker compose exec drupal ./vendor/bin/drush updb -y
docker compose exec drupal ./vendor/bin/drush cim -y
docker compose exec drupal ./vendor/bin/drush cr
```

---

## 5. Workflow thường dùng

### Sau khi thay đổi cấu hình trên local

```bash
docker compose exec drupal ./vendor/bin/drush cex -y
git status
git diff
git add config/sync
git commit -m "Update Drupal configuration"
```

### Sau khi pull branch của người khác

```bash
git pull
docker compose exec drupal composer install
docker compose exec drupal ./vendor/bin/drush updb -y
docker compose exec drupal ./vendor/bin/drush cim -y
docker compose exec drupal ./vendor/bin/drush cr
```

---

## 6. Config nào thường được export?

Ví dụ:

```text
node.type.knowledge_article.yml
field.storage.node.field_category.yml
field.field.node.knowledge_article.field_category.yml
core.entity_view_mode.node.article_card.yml
core.entity_view_display.node.knowledge_article.article_card.yml
views.view.knowledge_center.yml
taxonomy.vocabulary.article_category.yml
block_content.type.help_banner.yml
block.block.<machine_name>.yml
```

---

## 7. Config vs Content

### Configuration — export bằng `drush cex`

- Content types
- Field definitions
- Views
- View modes
- Block types
- Block placements
- Taxonomy vocabularies
- Permissions
- Image styles

### Content — không tự export bằng `drush cex`

- Nodes / Articles
- Taxonomy terms
- Users
- Uploaded files
- Content block instances

---

## 8. Lưu ý quan trọng

- Không commit thư mục random `sites/default/files/config_<hash>/sync` nếu mỗi máy tạo một hash khác nhau.
- Dùng **một `config_sync_directory` cố định cho toàn project**.
- Nên commit YAML config vào Git.
- Không chỉnh trực tiếp active config trong database.
- Luôn review `git diff` sau `drush cex`.
- `drush cr` không thay thế `drush cim`.

---

## 9. Docker project structure và mapping config

Với project Docker, một cấu trúc sạch và phổ biến là tách **infrastructure** khỏi **Drupal application**:

```text
drupal-local/
├── docker-compose.yml
├── Dockerfile
├── .env
└── src/
    ├── composer.json
    ├── composer.lock
    ├── config/
    │   └── sync/
    ├── vendor/
    └── web/
        ├── core/
        ├── modules/
        ├── themes/
        └── sites/
```

Trong cấu trúc này:

```text
drupal-local/
```

là Docker/infrastructure root, còn:

```text
drupal-local/src/
```

là Drupal/Composer project root.

### Volume mapping

Nếu `docker-compose.yml` có:

```yaml
volumes:
  - ./src:/var/www/html
```

thì mapping sẽ là:

```text
Host:
~/projects/drupal-local/src

Container:
/var/www/html
```

Vì Drupal root trong container là:

```text
/var/www/html/web
```

nên cấu hình:

```php
$settings['config_sync_directory'] = '../config/sync';
```

sẽ resolve thành:

```text
/var/www/html/config/sync
```

và được map về host:

```text
~/projects/drupal-local/src/config/sync
```

Đây là cấu hình đúng, không cần mount riêng `config/`.

### Kiểm tra

Trong container:

```bash
docker compose exec drupal ./vendor/bin/drush status
```

Nên thấy:

```text
Drupal root   : /var/www/html/web
Drupal config : ../config/sync
```

Kiểm tra file YAML trong container:

```bash
docker compose exec drupal ls -la /var/www/html/config/sync
```

Kiểm tra trên host:

```bash
ls -la src/config/sync
```

Hai vị trí trên phải phản ánh cùng dữ liệu nhờ bind mount.

---

## 10. Cấu trúc nào nên dùng?

Điểm quan trọng không phải project có thêm thư mục `src/` hay không, mà là **Drupal application root** nên có dạng:

```text
composer.json
composer.lock
config/
vendor/
web/
```

Hai kiểu sau đều hợp lý:

### Kiểu 1 — Drupal project ở repository root

```text
drupal-project/
├── composer.json
├── composer.lock
├── config/
├── vendor/
├── web/
└── docker-compose.yml
```

### Kiểu 2 — Docker root + Drupal app trong `src/`

```text
drupal-local/
├── docker-compose.yml
└── src/
    ├── composer.json
    ├── composer.lock
    ├── config/
    ├── vendor/
    └── web/
```

Nếu dùng Docker local như project hiện tại, kiểu 2 rất sạch vì tách:

```text
Infrastructure → docker-compose.yml, Dockerfile
Application    → src/
```

Với setup hiện tại:

```yaml
volumes:
  - ./src:/var/www/html
```

thì giữ nguyên cấu trúc này là hợp lý.

---

## 11. Git: nên commit gì?

Thường nên commit:

```text
composer.json
composer.lock
config/sync/
web/modules/custom/
web/themes/custom/
```

`settings.php` có commit hay không tùy chiến lược project và cách quản lý secret/environment config.

Thường không commit các dependency được Composer quản lý:

```text
vendor/
web/core/
web/modules/contrib/
web/themes/contrib/
```

Sau khi clone/pull project, dùng:

```bash
composer install
```

để khôi phục dependency theo `composer.lock`.


