# Vue.js Grading & Feedback Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a fully functional desktop-optimized Vue.js grading and feedback interface for both Lecturers and Students.

**Architecture:** A split two-column master-detail SPA that mounts inside `welcome.blade.php`. A top-level role switcher simulates Dosen vs Mahasiswa auth by logging in dynamically via seeded API credentials and storing the active JWT token in Axios defaults.

**Tech Stack:** Vue 3, Axios, Tailwind CSS v4, Laravel Vite.

---

### Task 1: Fix Backend JWT Namespace Compatibility Issues

**Files:**
- Modify: `config/jwt.php`
- Modify: `app/Http/Controllers/AuthController.php`

- [ ] **Step 1: Update config/jwt.php namespaces**
  Replace all `Tymon\JWTAuth` namespace references with `PHPOpenSourceSaver\JWTAuth` in `config/jwt.php` to prevent boot crashes.
  
- [ ] **Step 2: Update AuthController.php namespaces**
  Replace all `Tymon\JWTAuth` imports with `PHPOpenSourceSaver\JWTAuth` inside `app/Http/Controllers/AuthController.php`.

- [ ] **Step 3: Run existing assignment tests to verify boot correctness**
  Run: `php artisan test`
  Expected: Tests should boot successfully (tests may pass or fail depending on database setup, but the compile/boot namespace error must be resolved).

- [ ] **Step 4: Commit changes**
  ```bash
  git add config/jwt.php app/Http/Controllers/AuthController.php
  git commit -m "fix(auth): resolve Tymon namespace conflicts by migrating to PHPOpenSourceSaver"
  ```

---

### Task 2: Install Vue 3 and Configure Vite Plugin

**Files:**
- Modify: `package.json`
- Modify: `vite.config.js`

- [ ] **Step 1: Install vue, @vitejs/plugin-vue, and axios**
  Run: `npm install vue @vitejs/plugin-vue axios`
  Expected: Successful package installations, `package.json` updated.

- [ ] **Step 2: Add Vue plugin to vite.config.js**
  Modify `vite.config.js` to import and apply `@vitejs/plugin-vue`.
  ```javascript
  import { defineConfig } from 'vite';
  import laravel from 'laravel-vite-plugin';
  import tailwindcss from '@tailwindcss/vite';
  import vue from '@vitejs/plugin-vue';

  export default defineConfig({
      plugins: [
          laravel({
              input: ['resources/css/app.css', 'resources/js/app.js'],
              refresh: true,
          }),
          vue(),
          tailwindcss(),
      ],
  });
  ```

- [ ] **Step 3: Test compilation**
  Run: `npm run build`
  Expected: Compiles with Vite without errors.

- [ ] **Step 4: Commit changes**
  ```bash
  git add package.json package-lock.json vite.config.js
  git commit -m "feat(vite): install and configure Vue 3 compilation plugin"
  ```

---

### Task 3: Implement Main Vue App Entry Point

**Files:**
- Modify: `resources/js/app.js`
- Create [NEW]: `resources/js/components/GradeDashboard.vue`
- Modify: `resources/views/welcome.blade.php`

- [ ] **Step 1: Create the GradeDashboard container component**
  Create `resources/js/components/GradeDashboard.vue` with basic placeholder view and a Dosen/Mahasiswa toggle switch.
  
- [ ] **Step 2: Update app.js to mount Vue app**
  Replace `resources/js/app.js` contents to instantiate Vue 3 and mount `GradeDashboard.vue` on `#app`.
  ```javascript
  import { createApp } from 'vue';
  import GradeDashboard from './components/GradeDashboard.vue';

  createApp(GradeDashboard).mount('#app');
  ```

- [ ] **Step 3: Modify welcome.blade.php to host the Vue app**
  Replace the contents of `resources/views/welcome.blade.php` to clear out the default welcome layout and replace it with a simple container `<div id="app"></div>` loading `@vite(['resources/css/app.css', 'resources/js/app.js'])`.

- [ ] **Step 4: Run Vite dev server**
  Run: `npm run build`
  Expected: Asset compilation succeeds.

- [ ] **Step 5: Commit changes**
  ```bash
  git add resources/js/app.js resources/views/welcome.blade.php resources/js/components/GradeDashboard.vue
  git commit -m "feat(vue): mount Vue app in welcome blade page and prepare root dashboard container"
  ```

---

### Task 4: Build Lecturer Dashboard View (Split Master-Detail)

**Files:**
- Create [NEW]: `resources/js/components/LecturerView.vue`

- [ ] **Step 1: Create the Lecturer View component**
  Implement `resources/js/components/LecturerView.vue` featuring:
  - Left column: Classroom selector dropdown, assignments list dropdown, and student submissions list with status badges.
  - Right column: Detail workspace displaying selected student submission content, and a form to input or update a Score (0-100) and Feedback comments.
  - Axios integration to fetch classes, assignments, and submissions, and submit/update grades via `/api/grades`.

- [ ] **Step 2: Commit**
  ```bash
  git add resources/js/components/LecturerView.vue
  git commit -m "feat(vue): implement LecturerView component with split master-detail grading workspace"
  ```

---

### Task 5: Build Student Dashboard View (Assignments & Submissions)

**Files:**
- Create [NEW]: `resources/js/components/StudentView.vue`

- [ ] **Step 1: Create the Student View component**
  Implement `resources/js/components/StudentView.vue` featuring:
  - Left column: Classroom selector, assignments list with status badges.
  - Right column: Assignment instructions, lecturer's assigned score and feedback details card, and a text/file upload form to submit or resubmit work.
  - Axios integration to fetch student classes, assignments, and grades, and perform submission via `POST /api/submissions` and `PUT /api/submissions`.

- [ ] **Step 2: Commit**
  ```bash
  git add resources/js/components/StudentView.vue
  git commit -m "feat(vue): implement StudentView component for student assignments and grade reviews"
  ```

---

### Task 6: Integrate Views, Auth Simulation, and Build Assets

**Files:**
- Modify: `resources/js/components/GradeDashboard.vue`

- [ ] **Step 1: Update GradeDashboard.vue to connect components**
  Integrate `LecturerView` and `StudentView` in `GradeDashboard.vue`. Wire up the top role-switcher so that switching roles performs an Axios call to `/api/login` with credentials `lecturer@example.com` or `student@example.com`, sets `Axios.defaults.headers.common['Authorization'] = 'Bearer ' + token`, and renders the corresponding POV.

- [ ] **Step 2: Run build to bundle all assets**
  Run: `npm run build`
  Expected: Successful production build.

- [ ] **Step 3: Verification**
  Run manual verification (run the Laravel dev server, access the root index, log in as Dosen, submit a grade for Student, switch to Student POV, verify the grade card is visible, and test resubmission).

- [ ] **Step 4: Commit**
  ```bash
  git add resources/js/components/GradeDashboard.vue
  git commit -m "feat(vue): integrate Lecturer and Student POVs with token-based role authentication"
  ```
