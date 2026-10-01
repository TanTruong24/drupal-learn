# Linux cơ bản và Permission cần biết khi làm Drupal/Docker

Tài liệu này chỉ tập trung vào những kiến thức Linux thực tế bạn cần khi phát triển Drupal trong WSL2 + Docker.

---

## 1. Filesystem Linux và Windows khác nhau

Trong WSL:

```text
/home/root_wsl/...
```

là filesystem Linux.

Còn:

```text
/mnt/c/...
/mnt/d/...
```

là ổ Windows được mount vào Linux.

Với Drupal/Docker nên đặt source tại:

```text
/home/root_wsl/projects/drupal-local
```

thay vì:

```text
/mnt/c/Users/...
/mnt/d/workspace/...
```

Lý do chính: performance filesystem và behavior permission/file watching tốt hơn.

---

## 2. Các command Linux thường dùng

Xem thư mục hiện tại:

```bash
pwd
```

Về home:

```bash
cd ~
```

Đi vào project:

```bash
cd ~/projects/drupal-local
```

Liệt kê file:

```bash
ls
```

Xem chi tiết permission/owner:

```bash
ls -la
```

Tạo directory:

```bash
mkdir folder-name
```

Tạo cả parent directory nếu chưa tồn tại:

```bash
mkdir -p ~/projects/drupal-local
```

Copy file:

```bash
cp source destination
```

Xóa file:

```bash
rm file.txt
```

Xóa directory recursively:

```bash
rm -rf folder
```

> `rm -rf` rất mạnh. Linux không có Recycle Bin cho command này.

---

## 3. User hiện tại

Kiểm tra user:

```bash
whoami
```

Kiểm tra home:

```bash
echo $HOME
```

Ví dụ:

```text
whoami   -> root_wsl
$HOME    -> /home/root_wsl
```

Ký hiệu:

```text
~
```

chính là `$HOME`.

Ví dụ:

```bash
cd ~/projects
```

thực chất là:

```bash
cd /home/root_wsl/projects
```

---

## 4. `root`, `sudo` và user thường

Linux có superuser:

```text
root
```

`root` có quyền gần như toàn bộ hệ thống.

User bình thường chạy command cần quyền cao bằng:

```bash
sudo command
```

Ví dụ:

```bash
sudo apt update
```

Trong Docker:

```bash
docker compose exec -u root drupal ...
```

nghĩa là chạy command bên trong container với user `root`.

Ví dụ:

```bash
docker compose exec -u root drupal chown ...
```

thường cần `root` vì user bình thường không được tự ý đổi owner file.

---

## 5. Permission Linux: `rwx`

Mỗi file/directory có quyền cho 3 nhóm:

```text
user    group    others
```

Mỗi nhóm có thể có:

```text
r = read
w = write
x = execute
```

Ví dụ:

```text
-rw-r--r--
```

chia thành:

```text
-   rw-   r--   r--
    user  group others
```

Nghĩa là:

```text
owner:  read + write
group:  read
others: read
```

Đây chính là mode:

```text
644
```

---

## 6. Ý nghĩa số trong chmod

Giá trị:

```text
r = 4
w = 2
x = 1
```

Cộng lại:

```text
7 = rwx
6 = rw-
5 = r-x
4 = r--
```

Ví dụ phổ biến:

```text
644 = rw-r--r--
755 = rwxr-xr-x
664 = rw-rw-r--
775 = rwxrwxr-x
777 = rwxrwxrwx
```

---

## 7. `chmod`

`chmod` thay đổi permission.

Ví dụ:

```bash
chmod 644 settings.php
```

nghĩa là:

```text
owner:  read/write
group:  read
others: read
```

Directory:

```bash
chmod 755 sites/default
```

Directory cần `x` để có thể đi vào/traverse nó.

Vì vậy `x` trên directory không có nghĩa giống "execute file PHP".

---

## 8. File và directory nên dùng permission khác nhau

Thông thường:

```text
File:      644 hoặc 664
Directory: 755 hoặc 775
```

Không nên chạy:

```bash
chmod -R 775 folder
```

nếu folder có cả file và directory, vì lúc đó file cũng nhận executable bit.

Tốt hơn:

```bash
find folder -type d -exec chmod 775 {} \;
find folder -type f -exec chmod 664 {} \;
```

---

## 9. `chown`

`chmod` thay permission.

`chown` thay owner/group.

Cú pháp:

```bash
chown user:group file
```

Ví dụ:

```bash
chown www-data:www-data files
```

Recursive:

```bash
chown -R www-data:www-data files
```

---

## 10. `www-data` là gì?

Trong Debian/Ubuntu Apache thường chạy bằng user:

```text
www-data
```

Container Drupal hiện tại cũng dùng Apache Debian, nên PHP/Apache cần quyền ghi dưới identity `www-data`.

Ví dụ Drupal phải ghi vào:

```text
web/sites/default/files
```

nên ta dùng:

```bash
chown -R www-data:www-data web/sites/default/files
```

---

## 11. Permission Drupal nên hiểu như thế nào?

### `settings.php`

File:

```text
web/sites/default/settings.php
```

chứa configuration quan trọng, bao gồm database connection.

Sau install nên để:

```text
644
```

```bash
chmod 644 web/sites/default/settings.php
```

Apache chỉ cần đọc, không cần sửa file này trong runtime bình thường.

### `sites/default/files`

Directory:

```text
web/sites/default/files
```

Drupal cần ghi runtime để chứa:

- uploaded files;
- generated CSS;
- generated JavaScript;
- image styles;
- temporary/generated assets.

Vì vậy Apache phải có quyền ghi.

Khuyến nghị local:

```text
Directories: 775
Files:       664
Owner/group: www-data:www-data
```

---

## 12. Vì sao không nên dùng `777`?

```text
777 = rwxrwxrwx
```

nghĩa là mọi user đều có quyền read/write/execute.

Nó thường "fix nhanh" permission error nhưng che giấu nguyên nhân thật và không an toàn.

Tránh:

```bash
chmod -R 777 .
```

Chỉ trong trường hợp installer local cần ghi tạm, có thể dùng rất giới hạn rồi hạ permission lại.

---

## 13. Bind mount Docker và permission

Compose hiện có:

```yaml
volumes:
  - ./src:/var/www/html
```

Nghĩa là:

```text
WSL host
~/projects/drupal-local/src
       ↕
Docker container
/var/www/html
```

Đây không phải copy file.

Hai path đang trỏ tới cùng dữ liệu.

Do đó nếu trong container chạy:

```bash
chown -R www-data:www-data /var/www/html/web/sites/default/files
```

thì owner của file bên WSL host cũng thay đổi.

---

## 14. Permission có mất khi restart máy không?

Không.

Vì source nằm thật trong filesystem WSL:

```text
/home/root_wsl/projects/drupal-local/src
```

nên permission/owner được lưu trên filesystem.

Các thao tác sau không làm mất permission:

```bash
docker compose stop
docker compose start
docker compose down
docker compose up -d
```

Restart Windows/WSL/Docker Desktop cũng không làm mất.

Có thể phải setup lại khi:

- xóa directory và tạo lại;
- clone source mới;
- copy source từ Windows;
- restore project;
- script nào đó thay ownership/permission.

---

## 15. Bind mount và named volume khác nhau

Source Drupal:

```yaml
- ./src:/var/www/html
```

là bind mount.

Database:

```yaml
- db_data:/var/lib/mysql
```

là named volume.

Khác nhau:

```text
Bind mount
Host path cụ thể <-> container path
Thường dùng cho source code

Named volume
Docker quản lý storage
Thường dùng cho database/runtime data
```

Cẩn thận với:

```bash
docker compose down -v
```

`-v` có thể xóa named volume `db_data`, tức là xóa database local.

Nó không xóa `./src` bind mount.

---

## 16. Host path và container path

Bạn cần phân biệt rõ:

```text
Host WSL:
/home/root_wsl/projects/drupal-local/src

Container:
/var/www/html
```

Ví dụ file:

```text
Host:
~/projects/drupal-local/src/web/sites/default/settings.php

Container:
/var/www/html/web/sites/default/settings.php
```

Khi chạy Linux command trực tiếp trong WSL, dùng host path.

Khi chạy:

```bash
docker compose exec drupal ...
```

thì command đang chạy trong container, dùng container path.

---

## 17. Kiểm tra owner và permission

Host WSL:

```bash
ls -ld src/web/sites/default
ls -l src/web/sites/default/settings.php
ls -ld src/web/sites/default/files
```

Trong container:

```bash
docker compose exec drupal \
  ls -la /var/www/html/web/sites/default
```

Ví dụ:

```text
drwxrwxr-x www-data www-data files
-rw-r--r-- root     root     settings.php
```

Quan trọng không phải owner lúc nào cũng phải giống hệt ví dụ, mà là:

- Apache đọc được code/config;
- Apache ghi được `sites/default/files`;
- Apache không cần quyền sửa `settings.php` sau install.

---

## 18. `find` rất hữu ích khi fix permission

Directory:

```bash
find path -type d
```

File:

```bash
find path -type f
```

Kết hợp chmod:

```bash
find path -type d -exec chmod 775 {} \;
find path -type f -exec chmod 664 {} \;
```

Đây là pattern rất thường dùng khi deploy PHP/Drupal trên Linux.

---

## 19. Một số command cần nhớ

```bash
# Current path
pwd

# Current user
whoami

# Home
 echo $HOME

# List
ls -la

# Change directory
cd path

# Create directory
mkdir -p path

# Copy
cp source destination

# Permission
chmod 644 file
chmod 755 directory

# Owner
chown user:group file
chown -R user:group directory

# Find directories
find path -type d

# Find files
find path -type f
```

Docker:

```bash
# Run as normal container user
docker compose exec drupal command

# Run as root inside container
docker compose exec -u root drupal command
```

---

## 20. Mental model nên nhớ

```text
Permission = ai được làm gì?
chmod

Ownership = file thuộc về ai?
chown
```

Drupal example:

```text
settings.php
  Apache cần READ
  Apache không cần WRITE

sites/default/files
  Apache cần READ + WRITE
```

Và với Docker:

```text
WSL filesystem
       ↕ bind mount
Docker container
```

Thay đổi file/permission ở một phía thường được phản ánh sang phía còn lại.

---

## 21. Rule thực tế cho Drupal local

Có thể nhớ ngắn gọn:

```text
Code/config:
  644 file
  755 directory

Drupal writable files:
  664 file
  775 directory
  owner/group cho Apache ghi được

Không chmod -R 777 toàn project.
```

Đây là đủ để xử lý phần lớn lỗi permission bạn sẽ gặp khi học và deploy Drupal trên Linux.
