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

* Dosen membuat kelas
* Mahasiswa bergabung ke kelas
* List kelas per user
* List mahasiswa dalam kelas

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
app/
 ├── Models/
 ├── Http/
 │   ├── Controllers/
 │   ├── Middleware/
 ├── Services/ (opsional)

routes/
 └── api.php

database/
 └── migrations/
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
DB_DATABASE=edutask_db
DB_USERNAME=root
DB_PASSWORD=
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

## 📌 Endpoint API (Ringkasan)

### Auth

| Method | Endpoint      | Deskripsi     |
| ------ | ------------- | ------------- |
| POST   | /api/register | Register user |
| POST   | /api/login    | Login user    |
| GET    | /api/me       | Get profile   |

---

### Classes

| Method | Endpoint               |
| ------ | ---------------------- |
| POST   | /api/classes           |
| GET    | /api/classes           |
| GET    | /api/classes/{id}      |
| POST   | /api/classes/{id}/join |

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

* Register → Login → Token
* Akses endpoint dengan token
* Upload file submission

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