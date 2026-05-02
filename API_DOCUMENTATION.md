# API Documentation - ClassRoom & Enrollment (Anggota 2)

## Database Structure

### Tables
- **classes** - Tabel kelas yang dibuat oleh dosen
- **enrollments** - Tabel pendaftaran siswa ke kelas

---

## Models & Relations

### ClassRoom Model (`app/Models/ClassRoom.php`)
```
ClassRoom
├── belongsTo(User) → lecturer (dosen yang membuat)
├── hasMany(Enrollment)
└── belongsToMany(User) → students (melalui enrollments)
```

**Key Features:**
- Auto-generate `invite_code` (8 karakter random) saat create
- Method `hasStudent(studentId)` - cek apakah siswa terdaftar
- Method `isFull()` - cek apakah kelas penuh
- Method `getEnrolledCountAttribute()` - hitung jumlah siswa terdaftar

### Enrollment Model (`app/Models/Enrollment.php`)
```
Enrollment
├── belongsTo(ClassRoom)
└── belongsTo(User) → student
```

**Constraints:**
- Unique index pada `(class_id, student_id)` - prevent duplikat

### User Model (Updated)
```
User
├── hasMany(ClassRoom) → classes (untuk lecturer)
└── hasMany(Enrollment) → enrollments (untuk student)
```

---

## API Endpoints

### CLASS MANAGEMENT

#### 1. List All Classes
**GET** `/api/classes`
- **Auth:** Required (JWT)
- **Role:** Lecturer / Student
- **Response:**
  - Lecturer: Menampilkan semua kelas yang mereka buat
  - Student: Menampilkan semua kelas yang sudah mereka ikuti

```json
{
  "success": true,
  "message": "Daftar kelas Anda",
  "data": [
    {
      "id": 1,
      "name": "Web Development 101",
      "description": "Belajar web development dari dasar",
      "lecturer_id": 1,
      "invite_code": "ABC12345",
      "max_students": 30,
      "status": "active",
      "created_at": "2026-04-27T10:00:00Z",
      "updated_at": "2026-04-27T10:00:00Z",
      "enrolled_count": 15
    }
  ]
}
```

---

#### 2. Get Class Details
**GET** `/api/classes/{id}`
- **Auth:** Required (JWT)
- **Role:** Lecturer (pemilik) / Student (yang terdaftar)
- **Response:**

```json
{
  "success": true,
  "message": "Detail kelas",
  "data": {
    "id": 1,
    "name": "Web Development 101",
    "description": "Belajar web development dari dasar",
    "lecturer_id": 1,
    "invite_code": "ABC12345",
    "max_students": 30,
    "status": "active",
    "created_at": "2026-04-27T10:00:00Z",
    "updated_at": "2026-04-27T10:00:00Z",
    "enrolled_count": 15,
    "lecturer": {
      "id": 1,
      "name": "Dr. John Doe",
      "email": "lecturer@example.com",
      "role": "lecturer"
    },
    "students": [
      {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "role": "student"
      }
    ]
  }
}
```

---

#### 3. Create New Class
**POST** `/api/classes`
- **Auth:** Required (JWT)
- **Role:** Lecturer only
- **Request Body:**

```json
{
  "name": "Web Development 101",
  "description": "Belajar web development dari dasar",
  "max_students": 30,
  "status": "active"
}
```

- **Response:**

```json
{
  "success": true,
  "message": "Kelas berhasil dibuat",
  "data": {
    "id": 1,
    "name": "Web Development 101",
    "description": "Belajar web development dari dasar",
    "lecturer_id": 1,
    "invite_code": "ABC12345",
    "max_students": 30,
    "status": "active",
    "updated_at": "2026-04-27T10:00:00Z",
    "created_at": "2026-04-27T10:00:00Z"
  }
}
```

**Note:** `invite_code` otomatis di-generate, jangan kirim di request

---

#### 4. Update Class
**PUT** `/api/classes/{id}`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Request Body:**

```json
{
  "name": "Web Development Advanced",
  "description": "Lanjutan web development",
  "max_students": 40,
  "status": "active"
}
```

- **Response:**

```json
{
  "success": true,
  "message": "Kelas berhasil diperbarui",
  "data": {
    "id": 1,
    "name": "Web Development Advanced",
    "description": "Lanjutan web development",
    "lecturer_id": 1,
    "invite_code": "ABC12345",
    "max_students": 40,
    "status": "active"
  }
}
```

**Error (403 Forbidden):**
```json
{
  "success": false,
  "message": "Anda tidak berhak mengubah kelas ini"
}
```

---

#### 5. Delete Class
**DELETE** `/api/classes/{id}`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Response:**

```json
{
  "success": true,
  "message": "Kelas berhasil dihapus"
}
```

**Note:** Semua enrollment di kelas ini juga akan dihapus (CASCADE)

---

#### 6. Get Invite Code
**GET** `/api/classes/{id}/invite-code`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Response:**

```json
{
  "success": true,
  "data": {
    "class_id": 1,
    "invite_code": "ABC12345",
    "class_name": "Web Development 101"
  }
}
```

---

#### 7. Join Class Using Invite Code
**POST** `/api/classes/join-by-code`
- **Auth:** Required (JWT)
- **Role:** Student only
- **Request Body:**

```json
{
  "invite_code": "ABC12345"
}
```

- **Response:**

```json
{
  "success": true,
  "message": "Berhasil bergabung dengan kelas",
  "data": {
    "class": {
      "id": 1,
      "name": "Web Development 101",
      "description": "Belajar web development dari dasar",
      "lecturer_id": 1,
      "invite_code": "ABC12345",
      "max_students": 30,
      "status": "active"
    },
    "enrollment": {
      "id": 1,
      "class_id": 1,
      "student_id": 2,
      "enrolled_at": "2026-04-27T11:30:00Z",
      "status": "active",
      "created_at": "2026-04-27T11:30:00Z"
    }
  }
}
```

**Error Cases:**
```json
{
  "success": false,
  "message": "Kode undangan tidak valid atau kelas sudah tidak aktif"
}
```

```json
{
  "success": false,
  "message": "Anda sudah terdaftar di kelas ini"
}
```

```json
{
  "success": false,
  "message": "Kelas sudah penuh, tidak dapat bergabung"
}
```

---

### ENROLLMENT MANAGEMENT

#### 8. List All Students in Class
**GET** `/api/classes/{classId}/enrollments`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Response:**

```json
{
  "success": true,
  "message": "Daftar siswa di kelas",
  "data": {
    "class_id": 1,
    "class_name": "Web Development 101",
    "total_students": 2,
    "students": [
      {
        "id": 1,
        "student": {
          "id": 2,
          "name": "Jane Smith",
          "email": "jane@example.com"
        },
        "status": "active",
        "enrolled_at": "2026-04-27T11:30:00Z"
      },
      {
        "id": 2,
        "student": {
          "id": 3,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "status": "active",
        "enrolled_at": "2026-04-27T12:00:00Z"
      }
    ]
  }
}
```

---

#### 9. Manually Enroll Student
**POST** `/api/classes/{classId}/enrollments`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Request Body:**

```json
{
  "student_id": 2
}
```

- **Response:**

```json
{
  "success": true,
  "message": "Siswa berhasil ditambahkan ke kelas",
  "data": {
    "enrollment_id": 1,
    "student_id": 2,
    "class_id": 1,
    "status": "active"
  }
}
```

**Error Cases:**
```json
{
  "success": false,
  "message": "User bukan siswa"
}
```

```json
{
  "success": false,
  "message": "Siswa sudah terdaftar di kelas ini"
}
```

```json
{
  "success": false,
  "message": "Kelas sudah penuh, tidak dapat menambahkan siswa"
}
```

---

#### 10. Remove Student from Class
**DELETE** `/api/classes/{classId}/enrollments/{enrollmentId}`
- **Auth:** Required (JWT)
- **Role:** Lecturer only (harus pemilik kelas)
- **Response:**

```json
{
  "success": true,
  "message": "Siswa berhasil dihapus dari kelas"
}
```

---

#### 11. Check Student Enrollment
**GET** `/api/classes/{classId}/check-enrollment/{studentId}`
- **Auth:** Required (JWT)
- **Role:** Lecturer (pemilik) / Student (checking themselves)
- **Response:**

```json
{
  "success": true,
  "data": {
    "class_id": 1,
    "student_id": 2,
    "is_enrolled": true
  }
}
```

---

## Usage in Other Parts

### For Anggota 3 (Assignments)
- Gunakan `class_id` dari tabel `classes` untuk create assignment
- Validasi: pastikan `class_id` ada dan status `active`

```php
// Contoh validasi
$class = ClassRoom::find($request->class_id);
if (!$class) {
    return response()->json(['message' => 'Kelas tidak ditemukan'], 404);
}
```

### For Anggota 4 (Submissions)
- Check apakah student terdaftar di kelas:

```php
$isEnrolled = $class->hasStudent($studentId);
```

- Atau gunakan method di Enrollment model:

```php
$enrollment = Enrollment::where('class_id', $classId)
    ->where('student_id', $studentId)
    ->where('status', 'active')
    ->first();

if (!$enrollment) {
    // Student tidak terdaftar
}
```

### For Anggota 5 (Grades)
- Get semua students di kelas:

```php
$students = $class->students;
```

---

## Security Notes

✅ **Validasi Ownership:** Setiap edit/delete kelas, wajib check `lecturer_id`
✅ **Unique Enrollment:** Tidak bisa daftar 2x di kelas yang sama
✅ **Capacity Check:** Sistem cek apakah kelas penuh sebelum enroll
✅ **JWT Auth:** Semua endpoint protected dengan auth:api
✅ **Role Check:** Endpoint lecturer-only hanya bisa diakses dosen

---

## Migration Files

File yang sudah dibuat:
- `database/migrations/2026_04_27_000001_create_classes_table.php`
- `database/migrations/2026_04_27_000002_create_enrollments_table.php`

Jalankan:
```bash
php artisan migrate
```

---

## Summary

✅ ClassRoom model dengan auto-generate invite_code
✅ Enrollment model untuk track student registration
✅ Relasi many-to-many antara User (student) ↔ ClassRoom
✅ Full CRUD untuk class management
✅ Enrollment system (join by code + manual enroll)
✅ Semua security validations (ownership, capacity, duplicate)
✅ Ready untuk digunakan oleh Anggota 3, 4, 5
