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

* Mahasiswa submit tugas (upload file/gambar)
* Status submission (submitted / late)
* Validasi deadline
* Resubmit (opsional)

### 📊 Grading & Feedback

* Dosen memberikan nilai
* Feedback teks
* Update nilai
* Rekap nilai mahasiswa

---

## 🛠️ Tech Stack

* Laravel (Backend Framework)
* MySQL (Database)
* JWT Auth (`tymon/jwt-auth`)
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

Gunakan JWT Token pada setiap request:

```
Authorization: Bearer <your_token>
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

| Method | Endpoint                      |
| ------ | ----------------------------- |
| POST   | /api/assignments              |
| GET    | /api/assignments              |
| GET    | /api/assignments/{id}         |
| PATCH  | /api/assignments/{id}/publish |

---

### Submissions

| Method | Endpoint              |
| ------ | --------------------- |
| POST   | /api/submissions      |
| GET    | /api/submissions      |
| GET    | /api/submissions/{id} |

---

### Grades

| Method | Endpoint         |
| ------ | ---------------- |
| POST   | /api/grades      |
| GET    | /api/grades      |
| GET    | /api/grades/{id} |

---

## 🧠 Role & Permission

| Role      | Akses                             |
| --------- | --------------------------------- |
| Dosen     | Create class, assignment, grading |
| Mahasiswa | Join class, submit tugas          |

---

## 📦 Relasi Database

* User (dosen / mahasiswa)
* Class → dimiliki dosen
* Enrollment → relasi mahasiswa & class
* Assignment → milik class
* Submission → milik mahasiswa
* Grade → milik submission

---

## 📤 Upload File

* Disimpan di: `storage/app/public`
* Akses via:

```
php artisan storage:link
```

---

## 🧪 Testing

Gunakan:

* Postman

Test minimal:

- Register dosen & mahasiswa → login → dapat token
- Dosen: CRUD kelas, tambah/hapus enrollment, lihat invite code
- Mahasiswa: join kelas via kode, lihat kelas yang diikuti, cek enrollment

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