# Setup Guide - Anggota 2 (ClassRoom & Enrollment)

## ✅ Apa yang sudah dibuat

### 1. **Migrations** (Database Schema)
- `database/migrations/2026_04_27_000001_create_classes_table.php`
  - Tabel `classes` dengan columns: id, name, description, lecturer_id, invite_code (auto-generated), max_students, status, timestamps
  
- `database/migrations/2026_04_27_000002_create_enrollments_table.php`
  - Tabel `enrollments` dengan columns: id, class_id, student_id, enrolled_at, status, timestamps
  - Unique constraint pada (class_id, student_id)

### 2. **Models**
- `app/Models/ClassRoom.php`
  - Relations: belongsTo(User/lecturer), hasMany(Enrollment), belongsToMany(User/students)
  - Methods: hasStudent(), isFull(), getEnrolledCountAttribute()
  - Boot: Auto-generate invite_code saat create
  
- `app/Models/Enrollment.php`
  - Relations: belongsTo(ClassRoom), belongsTo(User/student)

- `app/Models/User.php` (Updated)
  - Relasi baru: hasMany(ClassRoom), hasMany(Enrollment)

### 3. **Controllers**
- `app/Http/Controllers/ClassRoomController.php`
  - index() - List kelas
  - show() - Detail kelas
  - store() - Buat kelas (lecturer only)
  - update() - Edit kelas (ownership validation)
  - destroy() - Hapus kelas (ownership validation)
  - joinByCode() - Siswa join kelas pakai invite code
  - getInviteCode() - Lihat kode undangan

- `app/Http/Controllers/EnrollmentController.php`
  - index() - List siswa di kelas
  - store() - Manual enroll siswa
  - destroy() - Remove siswa dari kelas
  - show() - Cek enrollment
  - checkEnrollment() - Cek apakah siswa terdaftar

### 4. **Routes** (Updated)
- `routes/api.php` - Semua endpoints sudah ditambahkan

---

## 🚀 Setup Steps

### Step 1: Run Migrations
```bash
php artisan migrate
```

Ini akan create tabel `classes` dan `enrollments`.

### Step 2: Test dengan Postman
Import file `postman_collection.json` ke Postman untuk testing semua endpoints.

**Catatan:** Ganti `YOUR_JWT_TOKEN_HERE` dengan token dari `/api/login`

---

## 📋 Quick Reference

### ClassRoom Workflow (Lecturer)
```
1. Create kelas → POST /api/classes
   ✓ invite_code otomatis di-generate (8 chars)
   
2. Get invite code → GET /api/classes/{id}/invite-code
   
3. Share code ke siswa
   
4. Lihat siapa saja yang udah join → GET /api/classes/{id}/enrollments
   
5. Manual add student (optional) → POST /api/classes/{id}/enrollments
   
6. Remove student → DELETE /api/classes/{id}/enrollments/{enrollmentId}
   
7. Edit atau hapus kelas → PUT/DELETE /api/classes/{id}
   ✓ Otomatis validate ownership (lecturer_id)
```

### Enrollment Workflow (Student)
```
1. Join kelas pakai invite code → POST /api/classes/join-by-code
   ✓ Request: { invite_code: "ABC12345" }
   ✓ Cek duplikat + capacity otomatis
   
2. List semua kelas saya → GET /api/classes
   ✓ Hanya show kelas yang sudah joined
   
3. Lihat detail kelas → GET /api/classes/{id}
   
4. Cek status enrollment → GET /api/classes/{id}/enrollments (khusus lecturer)
```

---

## 🔐 Security Implementation

✅ **Ownership Validation**
```php
if ($class->lecturer_id !== auth('api')->id()) {
    return response()->json(['message' => 'Forbidden'], 403);
}
```

✅ **Unique Enrollment**
- Database unique constraint pada (class_id, student_id)
- Aplikasi cek duplikat sebelum create

✅ **Capacity Check**
```php
if ($class->isFull()) {
    return response()->json(['message' => 'Kelas penuh'], 409);
}
```

✅ **JWT Authentication**
- Semua endpoint pakai middleware `auth:api`

---

## 🔗 Integration dengan Anggota Lain

### Untuk Anggota 3 (Assignments)
**Kebutuhan:** `class_id` yang valid

```php
// Validasi class ada dan active
$class = ClassRoom::where('id', $request->class_id)
    ->where('status', 'active')
    ->firstOrFail();

// Validasi pembuat assignment adalah lecturer dari kelas
if ($class->lecturer_id !== auth('api')->id()) {
    abort(403);
}
```

### Untuk Anggota 4 (Submissions)
**Kebutuhan:** Cek apakah student terdaftar di kelas

```php
// Method 1: Pakai hasStudent()
if (!$class->hasStudent($studentId)) {
    abort(403, 'Siswa tidak terdaftar di kelas ini');
}

// Method 2: Manual query
$isEnrolled = Enrollment::where([
    'class_id' => $classId,
    'student_id' => $studentId,
    'status' => 'active'
])->exists();
```

### Untuk Anggota 5 (Grades)
**Kebutuhan:** Get semua students di kelas

```php
// Method 1: Via relation
$students = $class->students; // Collection of User
$students = $class->students()->get();

// Method 2: Via enrollments
$enrollments = Enrollment::where('class_id', $classId)
    ->where('status', 'active')
    ->with('student')
    ->get();
```

---

## 💡 Key Points untuk Diingat

### 1. Model Names
- Model: `ClassRoom` (PascalCase)
- Tabel: `classes` (snake_case)
- ❌ **JANGAN** pakai nama `Class` - reserved keyword di PHP!

### 2. Invite Code
- Auto-generated saat create kelas
- Format: 8 karakter random
- Unique di database
- ❌ **JANGAN** minta user input untuk ini

### 3. Status & Capacity
- Status default: "active"
- max_students bisa null (unlimited)
- isFull() cek otomatis sebelum enroll

### 4. Timestamps
- created_at / updated_at otomatis
- enrolled_at di Enrollment table untuk track kapan siswa join

### 5. Relasi Many-to-Many
```
User ↔ ClassRoom (sebagai student)
Pivot: Enrollment table
Query: $class->students atau $user->enrollments
```

---

## 🧪 Testing Tips

### Test Lecturer Flow
```bash
# 1. Login sebagai lecturer
POST /api/login
{ email: lecturer@example.com, password: ... }

# 2. Create kelas
POST /api/classes
{ name: "Web Dev 101", description: "...", max_students: 30 }

# 3. Get invite code
GET /api/classes/1/invite-code
Response: { invite_code: "ABC12345" }

# 4. List siswa
GET /api/classes/1/enrollments
Response: { students: [] } (kosong)
```

### Test Student Flow
```bash
# 1. Login sebagai student
POST /api/login
{ email: student@example.com, password: ... }

# 2. Join kelas pakai code
POST /api/classes/join-by-code
{ invite_code: "ABC12345" }

# 3. List kelas saya
GET /api/classes
Response: hanya kelas yang sudah join
```

---

## 📝 API Endpoints Summary

| Method | Endpoint | Role | Purpose |
|--------|----------|------|---------|
| GET | /api/classes | All | List kelas |
| GET | /api/classes/{id} | All | Detail kelas |
| POST | /api/classes | Lecturer | Buat kelas |
| PUT | /api/classes/{id} | Lecturer | Edit kelas |
| DELETE | /api/classes/{id} | Lecturer | Hapus kelas |
| GET | /api/classes/{id}/invite-code | Lecturer | Lihat invite code |
| POST | /api/classes/join-by-code | Student | Join kelas |
| GET | /api/classes/{cId}/enrollments | Lecturer | List siswa |
| POST | /api/classes/{cId}/enrollments | Lecturer | Manual enroll |
| DELETE | /api/classes/{cId}/enrollments/{eId} | Lecturer | Remove siswa |
| GET | /api/classes/{cId}/check-enrollment/{sId} | All | Cek enrollment |

---

## ✨ Fitur Bonus

1. **Auto Invite Code Generate** - Tidak perlu input manual
2. **Capacity Management** - Cek otomatis apakah kelas penuh
3. **Ownership Validation** - Edit/delete hanya bisa owner
4. **Enrolled Count** - Query helper untuk hitung siswa
5. **Many-to-Many Relation** - Akses students dari class (via pivot)

---

## 📞 Contact Anggota Tim

Pertanyaan tentang:
- **ClassRoom & Enrollment** → Anggota 2 (Ini)
- **Assignments** → Anggota 3
- **Submissions** → Anggota 4
- **Grades** → Anggota 5
- **Authentication** → Anggota 1

---

**Status:** ✅ Ready untuk production
**Last Updated:** 2026-04-27
