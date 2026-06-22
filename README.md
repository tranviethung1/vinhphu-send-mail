   <p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

## Setup Development Environment

### Yêu cầu
- Docker & Docker Compose
- WSL2 (nếu sử dụng Windows)

### Các bước cài đặt

#### 1. Cấu hình file .env
Đầu tiên, copy file `.env.example` thành `.env` (nếu chưa có):
```bash
cp .env.example .env
```

Thêm các biến môi trường cần thiết vào cuối file `.env`:
```bash
echo -e "\nWWWUSER=1000\nWWWGROUP=1000" >> .env
```

Hoặc thêm thủ công vào file `.env`:
```
WWWUSER=1000
WWWGROUP=1000
```

#### 2. Sửa quyền truy cập file (nếu cần)
Nếu gặp lỗi permission denied khi save file:
```bash
sudo chown -R $USER:$USER /home/hungtv/vinhphu/mail-app
```

#### 3. Build Docker containers
```bash
docker compose build
```

Hoặc build lại từ đầu không dùng cache:
```bash
docker compose build --no-cache
```

#### 4. Khởi động các containers
```bash
docker compose up -d
```

#### 5. Tạo database và cấp quyền
```bash
# Tạo database vinhphu
docker compose exec mysql mysql -uroot -ppassword -e "CREATE DATABASE IF NOT EXISTS vinhphu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Cấp quyền cho user sail
docker compose exec mysql mysql -uroot -ppassword -e "GRANT ALL PRIVILEGES ON vinhphu.* TO 'sail'@'%'; FLUSH PRIVILEGES;"
```

#### 6. Chạy migrations
```bash
docker compose exec laravel.test php artisan migrate
```

#### 7. Tạo Application Key (nếu chưa có)
```bash
docker compose exec laravel.test php artisan key:generate
```

### Các lệnh hữu ích

#### Kiểm tra trạng thái containers
```bash
docker compose ps
```

#### Truy cập vào container Laravel
```bash
docker compose exec laravel.test bash
```

#### Xem logs
```bash
docker compose logs -f
```

#### Dừng containers
```bash
docker compose down
```

#### Khởi động lại containers
```bash
docker compose restart
```

#### Chạy Artisan commands
```bash
docker compose exec laravel.test php artisan [command]
```

Ví dụ:
```bash
# Tạo controller
docker compose exec laravel.test php artisan make:controller UserController

# Clear cache
docker compose exec laravel.test php artisan cache:clear

# Chạy seeder
docker compose exec laravel.test php artisan db:seed
```

#### Chạy Composer commands
```bash
docker compose exec laravel.test composer [command]
```

Ví dụ:
```bash
# Cài đặt dependencies
docker compose exec laravel.test composer install

# Update packages
docker compose exec laravel.test composer update
```

#### Chạy NPM commands
```bash
docker compose exec laravel.test npm [command]
```

Ví dụ:
```bash
# Cài đặt dependencies
docker compose exec laravel.test npm install

# Build assets
docker compose exec laravel.test npm run build

# Watch changes
docker compose exec laravel.test npm run dev
```

### Truy cập ứng dụng

- **Web Application**: http://localhost
- **Mailpit Dashboard**: http://localhost:8025
- **MySQL**: localhost:3306

### Database Configuration

Thông tin kết nối database (trong file `.env`):
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vinhphu
DB_USERNAME=sail
DB_PASSWORD=password
```

### Xử lý sự cố thường gặp

#### Lỗi permission denied
```bash
sudo chown -R $USER:$USER .
```

#### Lỗi "Access denied for user"
Chạy lại lệnh tạo database và cấp quyền (bước 5)

#### Container không khởi động được
```bash
# Xem logs để kiểm tra lỗi
docker compose logs

# Rebuild và restart
docker compose down
docker compose build
docker compose up -d
```

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
