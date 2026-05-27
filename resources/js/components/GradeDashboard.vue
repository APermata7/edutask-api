<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[#0a0a0a] text-gray-900 dark:text-[#ededec] p-6 flex flex-col items-center">
    <div class="w-full max-w-6xl">
      <!-- Header / Role Switcher -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 bg-white dark:bg-[#161615] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#3e3e3a]">
        <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-[#ededec]">EduTask Grading & Feedback</h1>
        <div class="flex items-center gap-3">
          <span class="text-xs font-semibold text-gray-500 dark:text-[#a1a09a] uppercase">Simulate Session:</span>
          <div class="flex gap-2">
            <button @click="setRole('lecturer')" :disabled="loading" :class="['px-4 py-1.5 rounded text-xs font-semibold border transition cursor-pointer', currentRole === 'lecturer' ? 'bg-[#f53003] dark:bg-[#ff4433] text-white border-[#f53003] dark:border-[#ff4433]' : 'bg-gray-100 dark:bg-[#262624] text-gray-700 dark:text-[#ededec] border-gray-300 dark:border-[#3e3e3a]']">
              Lecturer POV (Dosen)
            </button>
            <button @click="setRole('student')" :disabled="loading" :class="['px-4 py-1.5 rounded text-xs font-semibold border transition cursor-pointer', currentRole === 'student' ? 'bg-[#f53003] dark:bg-[#ff4433] text-white border-[#f53003] dark:border-[#ff4433]' : 'bg-gray-100 dark:bg-[#262624] text-gray-700 dark:text-[#ededec] border-gray-300 dark:border-[#3e3e3a]']">
              Student POV (Mahasiswa)
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Container -->
      <div v-if="loading" class="bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-20 text-center text-gray-500 dark:text-[#a1a09a]">
        Signing in to simulated session...
      </div>
      <div v-else-if="authError" class="bg-red-50 text-red-700 p-6 rounded-lg text-center border border-red-200">
        <p class="font-semibold mb-2">Simulated Authentication Failed</p>
        <p class="text-sm mb-4">{{ authError }}</p>
        <button @click="setRole(currentRole)" class="px-4 py-2 bg-red-700 text-white rounded text-xs font-semibold">Retry Login</button>
      </div>
      <div v-else>
        <!-- Lecturer View -->
        <LecturerView v-if="currentRole === 'lecturer'" :token="token" />

        <!-- Student View -->
        <StudentView v-else-if="currentRole === 'student'" :token="token" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import LecturerView from './LecturerView.vue';
import StudentView from './StudentView.vue';

export default {
  components: {
    LecturerView,
    StudentView
  },
  data() {
    return {
      currentRole: 'lecturer',
      token: '',
      loading: false,
      authError: ''
    }
  },
  mounted() {
    this.setRole('lecturer');
  },
  methods: {
    async setRole(role) {
      this.currentRole = role;
      this.loading = true;
      this.authError = '';
      this.token = '';

      const email = role === 'lecturer' ? 'lecturer@example.com' : 'student@example.com';
      const password = 'password';

      try {
        const res = await axios.post('/api/login', { email, password });
        if (res.data && res.data.access_token) {
          this.token = res.data.access_token;
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        } else {
          this.authError = 'No access token received from API.';
        }
      } catch (err) {
        console.error('Auth Simulation Error:', err);
        this.authError = err.response?.data?.message || 'Connection refused or database not seeded.';
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
