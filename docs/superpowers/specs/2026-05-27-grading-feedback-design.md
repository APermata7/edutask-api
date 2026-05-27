# Design Specification: Vue.js Grading & Feedback Frontend
**Date:** 2026-05-27  
**Topic:** Grading & Feedback Interface for Lecturers and Students  
**Status:** Approved  

---

## 1. Overview
The goal is to implement a Vue.js-based frontend inside the Laravel application specifically for the **Grading & Feedback** feature. The frontend will accommodate two roles:
* **Lecturer (Dosen)**: Review student submissions, assign scores, write feedback, and update/delete grades.
* **Student (Mahasiswa)**: View assignments, submit work (text links/files), view grades and feedback received from the lecturer, and resubmit revisions.

---

## 2. User Interface & Layout Design
We will implement **Option A: Split Two-Column Master-Detail Layout** optimized for desktop displays. The UI will inherit the typography (`Instrument Sans`) and color theme (light/dark adaptivity) of the default Laravel welcome page.

### Layout Structure
* **Session Switcher (Top Bar)**: A helper component for testing. It lets users switch roles between Lecturer and Student. Switching roles automatically fires a `/api/login` request using seeded accounts, retrieves a JWT token, and saves it in memory.
* **Sidebar (Left Column - 320px)**:
  * *Lecturer POV*: Classroom details selector, active assignment selector, and list of student submissions with status badges (*Submitted*, *Graded*, or *Late*).
  * *Student POV*: Enrolled classroom selector and list of assignments with status badges (*Not Submitted*, *Submitted*, or *Graded: Score*).
* **Workspace (Right Column - Flex-grow)**:
  * *Lecturer POV*: Displays the student's submission text/files, and a form to input or update a **Score (0-100)** and **Feedback comments**.
  * *Student POV*: Displays assignment instructions, their submitted response, and the lecturer's assigned score and feedback text. It also contains a form to submit or resubmit work.

---

## 3. Tech Stack & Frontend Setup
* **Vue 3**: Implemented via `@vitejs/plugin-vue` integrated with Laravel Vite.
* **Axios**: For API requests to the Laravel backend.
* **Tailwind CSS v4**: For styling, utilizing native CSS variables to automatically support light/dark system themes.
* **Single Page Entry**: Mounted inside the existing `welcome.blade.php` view.

---

## 4. API Endpoints Utilized
All requests will include the `Authorization: Bearer <JWT_TOKEN>` header.

* **Auth**: `POST /api/login` (to swap between test users `lecturer@example.com` and `student@example.com` with password `password`).
* **Classrooms**: `GET /api/classes` (lists classrooms).
* **Assignments**: `GET /api/classes/{classId}/assignments` (lists classroom assignments).
* **Submissions**: 
  * `GET /api/submissions` (filters by `assignment_id` to show submissions).
  * `POST /api/submissions` (creates a submission, handles file upload).
  * `PUT /api/submissions/{id}` (updates a submission / resubmit).
* **Grades**:
  * `POST /api/grades` (creates a grade and feedback text for a submission).
  * `PUT /api/grades/{id}` (updates a score and feedback).

---

## 5. Visual Styling System
To ensure text and background visibility in both Light and Dark themes:
* **Backgrounds**: Use CSS variables adapting to `prefers-color-scheme`.
  * Light: App background `#ffffff`, sidebar background `#fcfcfb`, card background `#f9f9f7`.
  * Dark: App background `#161615`, sidebar background `#0f0f0e`, card background `#1f1f1e`.
* **Typography**:
  * Light: Main text `#1b1b18`, secondary text `#706f6c`.
  * Dark: Main text `#ededec`, secondary text `#a1a09a`.
* **Borders**: Light `#19140025`, Dark `#3e3e3a`.
* **Accents**: Light `#f53003`, Dark `#ff4433`.

---

## 6. Testing Plan
* **Manual Verification**: Launch the server, open `/`, toggle the test role to Lecturer, grade a student submission, switch to Student view, and verify that the grade and feedback are instantly displayed. Test the resubmission flow from the Student view and verify that the Lecturer's list updates accordingly.
