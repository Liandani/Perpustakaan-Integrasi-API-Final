# 📚 Sistem Perpustakaan Integrasi Aplikasi (Microservices)

Proyek ini merupakan sistem manajemen perpustakaan berbasis arsitektur **Microservices** yang dibangun dengan framework **Laravel 13 / PHP 8.4**. Sistem ini mengintegrasikan komunikasi sinkron (REST API melalui API Gateway & GraphQL) dan komunikasi asinkron (Message Broker menggunakan RabbitMQ).

---

## 🏗️ Arsitektur & Daftar Layanan

Sistem terdiri dari beberapa service berikut yang saling terhubung:

| Nama Layanan | Direktori | Port (Docker) | Deskripsi |
| :--- | :--- | :--- | :--- |
| **API Gateway** | [`./api-gateway`](./api-gateway) | `8000` | Gateway utama untuk merutekan request ke microservices lainnya. |
| **User Service** | [`./sync/user-api`](./sync/user-api) | `8001` | Mengelola data pengguna (pendaftaran, detail, dan hapus user). |
| **Book Service** | [`./sync/book-api`](./sync/book-api) | `8002` | Mengelola inventaris buku dan ketersediaan buku. |
| **Loan Service** | [`./sync/loan-api`](./sync/loan-api) | `8003` | Mengelola peminjaman dan pengembalian buku. |
| **Fine Service** | [`./sync/fine-api`](./sync/fine-api) | `8004` | Menghitung denda secara otomatis jika ada keterlambatan pengembalian. |
| **GraphQL Service** | [`./sync/graphql-service`](./sync/graphql-service) | `8005` | Menyediakan antarmuka GraphQL / GraphiQL untuk query terpadu. |
| **Loan Worker** | [`./async/loan-worker`](./async/loan-worker) | `8011` | Worker asinkron untuk mempublikasikan pesan peminjaman ke RabbitMQ. |
| **Fine Worker** | [`./async/fine-worker`](./async/fine-worker) | `8012` | Worker asinkron untuk mengonsumsi pesan dari RabbitMQ. |
| **MySQL DB** | - | `3307` (host) | Database bersama dengan schema terpisah per service. |
| **RabbitMQ** | - | `5672` / `15672` (Admin UI) | Message broker untuk komunikasi asinkron event-driven. |

---

## ⚡ Prasyarat Sistem

1. **Docker Desktop** (Sangat Direkomendasikan) agar tidak perlu menginstal PHP, database, dan broker secara manual.

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### Menggunakan Docker Compose

1.  Pastikan **Docker Desktop** Anda sudah terbuka dan berjalan.
2.  Buka terminal/command prompt di direktori root proyek ini.
3.  Jalankan perintah berikut untuk mengompilasi dan mengaktifkan seluruh kontainer:
    ```bash
    docker-compose up --build -d
    ```
    *Perintah ini akan menginisialisasi database, mengunduh dependency library via Composer, membuat APP_KEY Laravel, menjalankan migrasi database, dan menghidupkan seluruh microservices.*
4.  Masukkan data awal (*dummy data*) dengan menjalankan perintah berikut sesuai dengan terminal yang Anda gunakan:
    *   **PowerShell**:
        ```powershell
        Get-Content seed_dummy_data.sql | docker exec -i mysql_db mysql -uroot -proot
        ```
    *   **CMD / Linux / Git Bash**:
        ```bash
        docker exec -i mysql_db mysql -uroot -proot < seed_dummy_data.sql
        ```

---


## 🧪 Panduan Cara Pengujian (Testing)

Proyek ini dapat diuji secara otomatis menggunakan skrip PowerShell bawaan atau secara interaktif dengan Postman.

### A. Pengujian Menggunakan Script PowerShell
Terdapat dua skrip PowerShell di direktori utama untuk melakukan pengetesan otomatis terhadap endpoint-endpoint API:

1.  **Pengujian Alur Lengkap Microservices (test-all-api.ps1)**:
    Menjalankan pengujian otomatis end-to-end dari pembuatan User baru, Buku baru, proses Peminjaman (Loan), proses Pengembalian lambat (Return) untuk memicu denda (Fine), hingga uji coba kirim pesan asinkron ke RabbitMQ.
    ```powershell
    .\test-all-api.ps1
    ```
2.  **Pengujian Alur Integrasi Sederhana (test-e2e.ps1)**:
    Menguji konektivitas dasar tiap microservice melalui gateway.
    ```powershell
    .\test-e2e.ps1
    ```

---

### B. Panduan Pengujian Menggunakan Postman (Lengkap)

Kami telah menyediakan file koleksi Postman siap pakai di root direktori dengan nama [api-collection.postman_collection.json](./api-collection.postman_collection.json).

Berikut adalah langkah-langkah detail cara menggunakannya:

#### Langkah 1: Impor Koleksi ke Postman
1. Buka aplikasi **Postman** di komputer Anda.
2. Di pojok kiri atas, klik tombol **Import**.
3. Pilih opsi **Files** atau *drag-and-drop* berkas `api-collection.postman_collection.json` dari folder proyek Anda.
4. Klik **Import** untuk mengonfirmasi. Koleksi bernama **Enterprise Application Integration API** akan muncul di sidebar kiri Anda.

#### Langkah 2: Memahami & Mengatur Variabel Lingkungan (Variables)
Koleksi ini menggunakan variabel internal Postman agar Anda tidak perlu mengetik alamat URL satu per satu.
1. Klik pada nama Koleksi (**Enterprise Application Integration API**) di sidebar kiri.
2. Pilih tab **Variables** di bagian atas menu koleksi.
3. Anda akan melihat daftar variabel seperti:
   *   `api_gateway_url`: `http://localhost:8000` (Gateway Utama)
   *   `user_api_url`: `http://localhost:8001` (User Service)
   *   `book_api_url`: `http://localhost:8002` (Book Service)
   *   `loan_api_url`: `http://localhost:8003` (Loan Service)
   *   `fine_api_url`: `http://localhost:8004` (Fine Service)
   *   `loan_worker_url`: `http://localhost:8011` (Loan Worker)
   *   `fine_worker_url`: `http://localhost:8012` (Fine Worker)

#### Langkah 3: Menjalankan Request Tes Satu per Satu
Koleksi dibagi menjadi 3 folder utama untuk mencakup semua skenario pengujian:

##### 1. Folder `1. API Gateway` (Tes Akses Terpusat - Sinkron & Asinkron)
Semua request di folder ini dikirimkan ke port `8000` (API Gateway) yang kemudian akan mem-proxy ke microservices terkait.
*   **Create User**: Membuat user baru. Kirim request ini untuk menyimpan data user baru ke database `user_db`.
*   **Create Book**: Membuat buku baru ke database `book_db`.
*   **Create Loan (Borrow Book)**: Mengirim data peminjaman buku (User ID & Book ID) ke database `loan_db`.
*   **Return Book**: Mengirim request pengembalian buku dengan mencantumkan tanggal pengembalian. Jika tanggal kembali melebihi tanggal jatuh tempo, sistem akan menghitung denda.
*   **Check Fine**: Memeriksa denda atas peminjaman tertentu ke database `fine_db`.
*   **Send Message to RabbitMQ**: Mengirim pesan event ke RabbitMQ secara asinkron dari Loan service.
*   **Consume Message from RabbitMQ**: Menarik antrean pesan dari RabbitMQ secara asinkron dari Fine worker.

##### 2. Folder `2. Sync API (REST - Direct)` (Tes Akses Langsung Ke Service)
Koleksi ini digunakan untuk menembak microservice individual secara langsung ke port masing-masing (bypass API Gateway) untuk memastikan tiap service independen berjalan dengan normal.
*   Cobalah request di dalam subfolder **Book Service** (port 8002), **User Service** (port 8001), **Loan Service** (port 8003), dan **Fine Service** (port 8004).

##### 3. Folder `3. Async API (RabbitMQ - Direct)` (Tes Integrasi Message Broker)
Menguji pengiriman pesan secara langsung ke antrean event.
*   **Loan Worker (PUBLISHER)** (port 8011): Mengirim event peminjaman buku ke antrean.
*   **Fine Worker (CONSUMER)** (port 8012): Menarik pesan dari antrean untuk diproses lebih lanjut.

---

## 📈 Uji Coba GraphQL (Interactive Playground)

Jika kontainer Docker sudah berjalan, Anda dapat berinteraksi dengan GraphQL menggunakan GraphiQL Playground:
*   Buka browser Anda dan akses: `http://localhost:8005/graphiql`
*   Di sana Anda dapat menuliskan query GraphQL untuk mengambil data terintegrasi (User, Book, dan Loan) secara dinamis dalam satu request.
