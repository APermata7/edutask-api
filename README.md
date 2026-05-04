# EduTask API

EduTask API adalah backend RESTful API berbasis Laravel yang digunakan untuk sistem manajemen tugas akademik. Sistem ini mendukung dua peran utama: **Dosen** dan **Mahasiswa**, dengan fitur lengkap mulai dari manajemen kelas, tugas, submission, hingga penilaian.

---

## 🚀 Fitur Utama

### 🔐 Authentication & User Management

* Register (Dosen / Mahasiswa)
* Login dengan JWT (JSON Web Token)
* Profile management
* Upload foto profil
* Role-based access control

### 🏫 Class & Enrollment

* Dosen membuat, mengedit, menghapus kelas
* Generate kode undangan unik per kelas (8 karakter)
* Mahasiswa bergabung ke kelas dengan kode undangan
* Dosen dapat menambah/mengeluarkan mahasiswa secara manual
* List kelas per user (dosen melihat kelas miliknya, mahasiswa melihat kelas yang diikuti)
* List mahasiswa dalam kelas (hanya dosen)
* Cek status keanggotaan mahasiswa
* Batasi jumlah mahasiswa per kelas (opsional)

### 📝 Assignment Management

* Dosen membuat tugas
* Status tugas (Draft / Published)
* Deadline enforcement
* Filter tugas berdasarkan kelas

### 📤 Submission System

* Mahasiswa submit tugas (upload file: gambar, PDF, zip, maks 5MB)
* Status submission (submitted / late)
* Validasi deadline (terlambat otomatis status `late`)
* Resubmit (revisi) – mahasiswa dapat mengirim ulang
* Dosen dapat melihat daftar submission dan menghapus submission

### 📊 Grading & Feedback

* Dosen memberikan nilai
* Feedback teks
* Update nilai
* Rekap nilai mahasiswa

---

## 🛠️ Tech Stack

* Laravel (Backend Framework)
* MySQL (Database)
* JWT Auth (`php-open-source-saver/jwt-auth`)
* REST API Architecture

---

## 📁 Struktur Project

```
edutask-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Models/
├── bootstrap/
│   └── app.php
├── config/
│   ├── auth.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── storage/
│   └── app/public/avatars/
│   └── app/public/submissions/
├── public/
│   └── storage/ 
└── .env
```

---

## ⚙️ Instalasi

1. Clone repository:

```
git clone https://github.com/APermata7/edutask-api.git
cd edutask-api
```

2. Install dependency:

```
composer install
```

3. Copy file environment:

```
cp .env.example .env
```

4. Konfigurasi database di `.env`:

```
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Generate key:

```
php artisan key:generate
```

6. Setup JWT:

```
php artisan jwt:secret
```

7. Jalankan migration:

```
php artisan migrate
```

8. Jalankan server:

```
php artisan serve
```

---

## 🔑 Authentication

Gunakan JWT Token pada setiap request. Token dikirim melalui header `Authorization: Bearer <token>`.  

```
Refresh token dapat digunakan untuk memperoleh token baru tanpa harus login ulang. Token yang sudah di-refresh akan masuk blacklist dan tidak dapat digunakan kembali.
```

---

## 📌 Endpoint API

### Auth & User Management

| Method | Endpoint              | Deskripsi                              |
| ------ | --------------------- | -------------------------------------- |
| POST   | /api/register         | Register user (lecturer/student)       |
| POST   | /api/login            | Login & get JWT token                  |
| GET    | /api/me               | Get authenticated user profile         |
| PUT    | /api/profile          | Update profile (name, email, password) |
| POST   | /api/profile/avatar   | Upload profile picture (max 2MB)       |
| POST   | /api/logout           | Logout & invalidate token              |
| POST   | /api/refresh          | Refresh JWT token                      |

---

### Classes & Enrollment

| Method | Endpoint                                         | Role yang boleh | Deskripsi                                      |
|--------|--------------------------------------------------|----------------|------------------------------------------------|
| POST   | /api/classes                                     | dosen          | Membuat kelas baru (generate invite_code otomatis) |
| GET    | /api/classes                                     | dosen/mahasiswa | Dosen: lihat kelas miliknya; Mahasiswa: lihat kelas yang diikuti |
| GET    | /api/classes/{id}                                | dosen/mahasiswa | Detail kelas (dosen pemilik atau mahasiswa yang terdaftar) |
| PUT    | /api/classes/{id}                                | dosen (pemilik) | Update kelas (name, description, max_students, status) |
| DELETE | /api/classes/{id}                                | dosen (pemilik) | Hapus kelas (beserta semua enrollments)       |
| GET    | /api/classes/{id}/invite-code                    | dosen (pemilik) | Mendapatkan kode undangan kelas               |
| POST   | /api/classes/join-by-code                        | mahasiswa       | Mahasiswa bergabung ke kelas menggunakan kode undangan 8 karakter |
| GET    | /api/classes/{classId}/enrollments               | dosen (pemilik) | Melihat daftar mahasiswa yang terdaftar di kelas |
| POST   | /api/classes/{classId}/enrollments               | dosen (pemilik) | Dosen menambahkan mahasiswa langsung (body: `{"student_id": id}`) |
| DELETE | /api/classes/{classId}/enrollments/{enrollmentId}| dosen (pemilik) | Dosen mengeluarkan mahasiswa dari kelas       |
| GET    | /api/classes/{classId}/check-enrollment/{studentId} | dosen/mahasiswa | Mengecek apakah seorang mahasiswa sudah terdaftar di kelas |
---

### Assignments

| Method | Endpoint                               | Role yang boleh | Deskripsi                                      |
|--------|----------------------------------------|----------------|------------------------------------------------|
| POST   | /api/assignments                       | dosen          | Membuat tugas baru (class_id, title, description, due_at) |
| GET    | /api/assignments                       | dosen/mahasiswa | Dosen: semua tugas; Mahasiswa: tugas dari kelas yang diikuti |
| GET    | /api/assignments/{assignment}          | dosen/mahasiswa | Detail tugas (akses terbatas jika mahasiswa harus terdaftar di kelas) |
| PUT    | /api/assignments/{assignment}          | dosen (pemilik) | Mengupdate tugas (title, description, due_at, dll) |
| PATCH  | /api/assignments/{assignment}/publish  | dosen (pemilik) | Mempublikasikan tugas (status berubah menjadi published) |
| DELETE | /api/assignments/{assignment}          | dosen (pemilik) | Menghapus tugas                               |
| GET    | /api/classes/{classId}/assignments     | dosen/mahasiswa | Mendaftar tugas berdasarkan kelas (filter)    |

---

### Submissions

| Method | Endpoint                         | Role yang boleh | Deskripsi                                      |
|--------|----------------------------------|----------------|------------------------------------------------|
| POST   | /api/submissions                 | mahasiswa      | Mengumpulkan tugas (upload file: pdf, gambar, zip, maks 5MB) |
| GET    | /api/submissions                 | dosen/mahasiswa | Dosen: semua submission; Mahasiswa: submission miliknya |
| GET    | /api/submissions/{submission}    | dosen/mahasiswa | Detail submission (dosen: semua; mahasiswa: milik sendiri) |
| PUT    | /api/submissions/{submission}    | mahasiswa      | Resubmit (revisi) tugas – upload file baru, konten diperbarui |
| DELETE | /api/submissions/{submission}    | dosen          | Hapus submission (hanya dosen pemilik kelas)  |

---

### Grades

| Method | Endpoint                         | Role yang boleh | Deskripsi                                      |
|--------|----------------------------------|----------------|------------------------------------------------|
| POST   | /api/grades                      | dosen          | Memberikan nilai pada submission (wajib submission_id, score) |
| GET    | /api/grades                      | dosen/mahasiswa | Dosen: semua grade; Mahasiswa: grade miliknya |
| GET    | /api/grades/{id}                 | dosen/mahasiswa | Detail grade (dosen: semua; mahasiswa: milik sendiri) |
| PUT    | /api/grades/{id}                 | dosen          | Mengupdate grade (score/feedback)             |
| DELETE | /api/grades/{id}                 | dosen          | Menghapus grade                               |
| POST   | /api/classes/{classId}/grade     | dosen          | Alternatif memberi nilai dengan validasi kelas |

---

## 🧠 Role & Permission

| Role      | Akses                                                           |
| --------- | --------------------------------------------------------------- |
| Dosen     | Create class, manage enrollment, CRUD assignment, **lihat submission, hapus submission, beri nilai (grading)** |
| Mahasiswa | Join class, lihat kelas terdaftar, **read assignment, submit tugas, resubmit, lihat submission sendiri** |

---

## 📦 Relasi Database

* User (dosen / mahasiswa)
* Class → dimiliki dosen
* Enrollment → relasi mahasiswa & class
* Assignment → milik class
* Submission → milik assignment & mahasiswa
* Grade → milik submission (tabel grades terpisah dengan relasi belongsTo)

---

## 📤 Upload File

* Avatar disimpan di: `storage/app/public/avatars`
* Submission file disimpan di: `storage/app/public/submissions`
* Akses via:

```
php artisan storage:link
```

---

## 🧪 Testing

Gunakan **Postman** untuk menguji endpoint. Berikut daftar minimal pengujian per modul:

### 🔐 Auth & User 
- Register dosen & mahasiswa → validasi email unik, role
- Login → mendapatkan token JWT
- Get profile (`/me`) → data user yang sedang login
- Update profile (name, email, password)
- Upload avatar (maks 2MB, format gambar)
- Refresh token → menghasilkan token baru, token lama masuk blacklist
- Logout → invalidate token
- Error autentikasi → JSON 401 dengan pesan spesifik (invalid, blacklisted)

### 🏫 Class & Enrollment 
- Dosen: CRUD kelas, tambah/hapus enrollment, lihat invite code
- Mahasiswa: join kelas via kode, lihat kelas yang diikuti, cek enrollment

### 📝 Assignment 
- Dosen: create, read, update, publish, delete assignment
- Mahasiswa: read assignment dari kelas yang diikuti

### 📤 Submission
- Mahasiswa: submit tugas (upload file), resubmit, lihat submission sendiri
- Dosen: lihat semua submission, hapus submission

### 📊 Grading
- Dosen: create, read, update, delete grade
- Mahasiswa: read grade miliknya

---

## 👥 Pembagian Tugas Tim

|           Anggota          |       Modul        |
| -------------------------- | ------------------ |
| 1. Aulia Permata Kumala    | Auth & User        |
| 2. Ciello Belleza Z.S      | Class & Enrollment |
| 3. Lovely Ito Panjaitan    | Assignment         |
| 4. Verda Aulia Setri       | Submission         |
| 5. Poeti Jelita            | Grading            |

---