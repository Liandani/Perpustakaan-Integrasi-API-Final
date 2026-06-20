# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan di dalam file ini.

## [0.4] - 2026-06-14

### Added (Ditambahkan)
- **GraphQL BFF (Backend-For-Frontend):** Membuat layanan baru `sync/graphql-service` menggunakan `nuwave/lighthouse` sebagai lapisan agregator data.
- **GraphiQL UI:** Mengintegrasikan `mll-lab/laravel-graphiql` sebagai antarmuka interaktif untuk mempermudah eksekusi dan eksplorasi kueri GraphQL langsung dari *browser*.
- **API Gateway GraphQL Proxy:** Menambahkan rute dinamis di `api-gateway` untuk meneruskan lalu lintas `/graphql` dan `/graphiql` ke `graphql-service`.

### Fixed (Diperbaiki)
- **Bug Raw HTML GraphiQL:** Memperbaiki *bug* di mana antarmuka GraphiQL dirender sebagai teks mentah (*raw code*) oleh *browser*. Diselesaikan dengan meneruskan *header* `Content-Type` asli dari layanan *backend* alih-alih melakukan *hardcode* `application/json` di API Gateway.
- **Bug __PHP_Incomplete_Class pada Cache AST:** Memperbaiki *Internal Server Error* saat membaca memori cache AST Lighthouse akibat ketidakcocokan serialisasi kelas. Diatasi dengan menonaktifkan *Query Cache* (`LIGHTHOUSE_QUERY_CACHE_ENABLE=false`) untuk tahap pengembangan lokal.
- **Bug Missing Resolvers:** Memperbaiki *error* `Could not locate a field resolver` dengan membuat kelas *resolver* kustom secara eksplisit untuk `Loan.php` dan `Fine.php`.
- **Bug Nullable Field di GraphQL:** Memperbaiki *error* nilai *null* pada daftar denda dan peminjaman dengan merombak kode *resolver* agar secara cerdas mengekstrak kunci `data` (`$response->json('data')`) dari balikan JSON layanan REST API.
- **Mapping Skema Field:** Menyelesaikan ketidakcocokan antara nama atribut API (`total_fine`) dan skema GraphQL (`amount`) menggunakan direktif `@rename(attribute: "total_fine")` agar kueri GraphQL tetap kompatibel tanpa perlu penyesuaian dari sisi *client*.

---

## [0.3] - 2026-06-14### Changed (Diubah)
- **Refactoring Arsitektur Monolitik:** Merombak `LoanController` dan `FineController` dengan menghapus seluruh referensi relasi Eloquent ORM lintas *database* (seperti `with(['user', 'book'])`). Setiap layanan kini beroperasi murni independen.
- **Dynamic API Gateway:** Menghapus *mock response* (data palsu statis) di `api-gateway` dan merombak file `routes/web.php` menjadi *Dynamic Proxy*. Semua trafik klien ke Port 8000 sekarang diarahkan (*forwarded*) secara cerdas ke *port* internal mikroservis yang relevan.
- **Format DateTime MySQL:** Menyesuaikan implementasi tipe data `due_date` dan `return_date` di `FineController` dengan melakukan parsing `Carbon` untuk menghindari *error* *Strict Mode* (`Invalid datetime format`) di MySQL.

### Added (Ditambahkan)
- **Skema Migrasi Terdistribusi:**
  - Memisahkan dan membuat ulang struktur migrasi tabel `loans` dan `loan_histories` di dalam *Loan API*.
  - Membuat tabel struktur migrasi `fines` terpisah di dalam *Fine API*.
- **Komunikasi Antar Layanan (HTTP Request):** Mengimplementasikan pemanggilan *HTTP facade* bawaan Laravel pada *Fine API* agar dapat mengambil secara dinamis data peminjaman dari *Loan API* sesuai dengan prinsip *microservices*.
- **Skrip Seeding Dummy Data:** Menambahkan skrip `seed_dummy_data.sql` dan alur penanaman *dummy data* SQL yang disesuaikan (*adapted*) dari *monolithic scheme* sebelumnya ke dalam masing-masing *database* mikroservis (`user_db`, `book_db`, `loan_db`).
- **Skrip Comprehensive E2E Test:** Membuat skrip PowerShell `test-all-api.ps1` yang menyimulasikan aliran proses bisnis secara sempurna (CRUD User/Book -> Pinjam -> Telat Kembali -> Denda -> RabbitMQ Flow) melalui gerbang utama *API Gateway*.

### Fixed (Diperbaiki)
- **Bug 500 API Gateway Proxy Method:** Memperbaiki kesalahan *method* `Http::send` pada layanan proksi dengan menyelaraskannya ke penulisan konstruktor statis dinamis `Http::$method()` di ekosistem Laravel 13.
- **Bug 500 Session & Cache Gateway:** Memperbaiki masalah sistem yang macet karena berusaha mencari `database.sqlite` (untuk mengatur sesi internal API Gateway). Menyelesaikannya dengan pembuatan *file* basis data secara eksplisit dan proses migrasi agar API Gateway bisa *boot-up*.
- **Bug 419 Page Expired (CSRF):** Menonaktifkan *middleware* proteksi CSRF di `bootstrap/app.php` untuk `loan-api`, `fine-api`, dan `api-gateway` sehingga panggilan layanan proksi (*server-to-server*) dan eksekusi dari CLI/Powershell dapat berjalan tanpa penolakan token sesi keamanan.

---

## [0.2] - 2026-06-14

### Added
- Menambahkan skrip pengujian E2E otomatis (`test-e2e.ps1`) menggunakan PowerShell untuk validasi REST API dan RabbitMQ.
- Mengimplementasikan variabel lingkungan dinamis (`env()`) pada kontroler untuk URL layanan dan RabbitMQ Host untuk menggantikan konfigurasi statis (*hardcode*).
- Menambahkan perintah *generation* `APP_KEY` secara langsung ke dalam `Dockerfile` untuk memastikan tersedianya kunci enkripsi pada saat *build*.

### Fixed
- Memperbaiki kegagalan proses *build* Docker akibat `composer install` (Exit Code 4) dengan memperbarui *build context* menjadi *root directory* dan menyesuaikan instruksi `COPY`.
- Memperbaiki kegagalan resolusi *local symlink* di dalam kontainer Docker dengan mengubah instruksi `composer install` menjadi `composer update`.
- Memperbaiki *Bug 500 Internal Server Error* (`MissingAppKeyException`) akibat pengecualian `.dockerignore` dengan menyalin paksa `.env.example` menjadi `.env` pada langkah *build* Docker.
- Memperbaiki *Bug 500 Internal Server Error* (`SQLiteDatabaseDoesNotExistException`) di Laravel 13 akibat ketiadaan file database untuk *session driver* bawaan dengan mengeksekusi inisialisasi file SQLite dan migrasi.

---

## [0.1] - 2026-06-13

### Changed (Diubah)
- **Struktur Repositori ke Monorepo Bersih:** Menghapus seluruh kerangka bawaan Laravel (seperti folder `app/`, `routes/`, `config/`, `database/`, dan `tests/`) dari direktori utama (root).
  - *Alasan:* Direktori utama sebelumnya adalah aplikasi Monolith utuh, padahal di dalamnya terdapat folder-folder microservices. Hal ini menyebabkan kerancuan di mana logika sebenarnya dijalankan. Dengan menghapusnya, root kini murni menjadi *wadah* pengelola (Monorepo) untuk layanan-layanan di dalamnya.
- **Pembaruan Konfigurasi Docker:** Memodifikasi `docker-compose.yml` untuk menyesuaikan dengan jalur (*path*) *build* dari struktur folder `sync/` dan `async/` yang baru.

### Added (Ditambahkan)
- **Pemisahan Jalur Sync & Async:** Membuat folder `sync/` untuk layanan HTTP (REST API) dan `async/` untuk layanan pemrosesan *background* (Queue Workers/Cron). Layanan lama dipindahkan menjadi `sync/book-api`, `sync/user-api`, dll.
  - *Detail:* Memindahkan *Controllers* bisnis yang dulunya berada di direktori utama (`app/Http/Controllers/BookController.php`, dsb.) langsung ke dalam masing-masing API service di folder `sync/`. Menggandakan kerangka aplikasi untuk `async/worker`.
  - *Alasan:* Pemisahan ini mengikuti pola arsitektur *event-driven* tingkat lanjut. Layanan `sync` hanya bertugas melayani HTTP Request dari *user* secepat mungkin, sedangkan proses berat (pengiriman email, perhitungan denda harian) dikerjakan oleh layanan `async`. Ini membuat skalabilitas lebih presisi (Anda bisa menambah server untuk API tanpa membuang sumber daya untuk *worker*, dan sebaliknya).
- **Paket Core Bersama (`packages/shared-core`):** Membuat *local composer package* baru yang berisi struktur Model database (seperti `User.php`, `Book.php`, `Loan.php`, `Fine.php`).
  - *Alasan:* Karena sekarang aplikasi API (`sync`) dan aplikasi *worker* (`async`) berdiri sendiri-sendiri secara fisik namun menggunakan basis data yang sama, mereka membutuhkan Model yang sama. Memusatkannya ke dalam satu paket mencegah terjadinya duplikasi kode (DRY - *Don't Repeat Yourself*).
- **Pembaruan Dependensi Antar Layanan:** Menambahkan skrip referensi Repositori Lokal (`type: path`) ke dalam `composer.json` pada masing-masing layanan agar bisa mengenali dan memuat `enterprise/shared-core`.
- **API Gateway Placeholder:** Menyiapkan folder `api-gateway` baru di struktur root.
  - *Alasan:* Disiapkan sebagai gerbang tunggal (*Single Point of Entry*) bagi klien *frontend* atau pihak ketiga untuk mengakses berbagai layanan mikro di belakang layar tanpa perlu mengetahui letak *port* masing-masing layanan.
- **Inisialisasi & Pengujian Awal (`sync/book-api`):** Menjalankan `composer update`, membuat file `.env`, mengatur *Application Key*, dan menjalankan pengujian (`php artisan test`) khusus di layanan `sync/book-api`.
  - *Alasan:* Untuk membuktikan dan memverifikasi bahwa struktur monorepo yang baru, termasuk pemanggilan paket lokal `shared-core`, dapat berjalan tanpa *error* autoloading.
