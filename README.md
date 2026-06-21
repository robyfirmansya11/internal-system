# Internal System

Internal System adalah aplikasi berbasis Laravel dan Filament yang digunakan untuk mengelola proses internal perusahaan, termasuk pengajuan, approval, dan administrasi data operasional.

## Teknologi

* PHP 8.3+
* Laravel 12
* Filament
* MySQL / MariaDB
* Tailwind CSS
* Vite

## Persyaratan

Pastikan perangkat telah terinstall:

* PHP 8.3 atau lebih baru
* Composer
* Node.js
* MySQL / MariaDB
* Git

## Instalasi

Clone repository:

```bash
git clone https://github.com/robyfirmansya11/internal-system.git
cd internal-system
```

Install dependency PHP:

```bash
composer install
```

Salin file environment:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Konfigurasi database pada file `.env`.

Jalankan migrasi database:

```bash
php artisan migrate
```

Install dependency frontend:

```bash
npm install
```

Build asset:

```bash
npm run build
```

Atau untuk development:

```bash
npm run dev
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

## Akun Administrator

Buat akun administrator menggunakan:

```bash
php artisan make:filament-user
```

## Maintenance

Membersihkan cache:

```bash
php artisan optimize:clear
```

Optimasi aplikasi:

```bash
php artisan optimize
```

## Deployment

Setelah melakukan perubahan:

```bash
git add .
git commit -m "Update application"
git push
```

## Author

Roby Firmansyah
