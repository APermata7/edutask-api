<template>
  <div class="grid grid-cols-1 md:grid-cols-[320px_1fr] gap-6 min-h-[500px]">
    <!-- Left Column: Master List -->
    <div class="bg-[#fcfcfb] dark:bg-[#0f0f0e] border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-4 flex flex-col gap-4">
      <div>
        <label class="block text-xs font-semibold text-gray-500 dark:text-[#a1a09a] uppercase mb-2">Classroom</label>
        <select v-model="selectedClassId" @change="fetchAssignments" class="w-full p-2 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec]">
          <option :value="null" disabled>Select Class</option>
          <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div v-if="selectedClassId">
        <label class="block text-xs font-semibold text-gray-500 dark:text-[#a1a09a] uppercase mb-2">Assignment</label>
        <select v-model="selectedAssignmentId" @change="fetchSubmissions" class="w-full p-2 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec]">
          <option :value="null" disabled>Select Assignment</option>
          <option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.title }}</option>
        </select>
      </div>

      <div v-if="selectedAssignmentId" class="flex-1 flex flex-col overflow-hidden">
        <label class="block text-xs font-semibold text-gray-500 dark:text-[#a1a09a] uppercase mb-2">Submissions ({{ submissions.length }})</label>
        <div class="flex-1 overflow-y-auto max-h-[300px] flex flex-col gap-2">
          <div v-for="sub in submissions" :key="sub.id" @click="selectSubmission(sub)" :class="['p-3 rounded-lg border cursor-pointer transition text-left', selectedSubmission && selectedSubmission.id === sub.id ? 'bg-[#fff2f2] dark:bg-[#2d100c] border-[#f53003] dark:border-[#ff4433]' : 'border-gray-200 dark:border-[#3e3e3a] hover:bg-gray-100 dark:hover:bg-[#262624]']">
            <div class="font-medium text-gray-900 dark:text-[#ededec]">{{ sub.student ? sub.student.name : 'Unknown' }}</div>
            <div class="text-xs text-gray-500 dark:text-[#a1a09a] flex items-center gap-2 mt-1">
              <span>{{ formatDate(sub.submitted_at) }}</span>
              <span v-if="sub.grade !== null" class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Graded: {{ sub.grade }}</span>
              <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Submitted</span>
              <span v-if="sub.status === 'late'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Late</span>
            </div>
          </div>
          <div v-if="submissions.length === 0" class="text-sm text-gray-500 dark:text-[#a1a09a] py-4 text-center">No submissions yet</div>
        </div>
      </div>
    </div>

    <!-- Right Column: Detail / Workspace -->
    <div class="bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-6">
      <div v-if="selectedSubmission">
        <div class="flex justify-between items-start border-b border-gray-200 dark:border-[#3e3e3a] pb-4 mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-[#ededec]">{{ selectedSubmission.student ? selectedSubmission.student.name : 'Unknown' }} - Submission</h3>
            <p class="text-sm text-gray-500 dark:text-[#a1a09a]">Email: {{ selectedSubmission.student ? selectedSubmission.student.email : '' }}</p>
          </div>
          <div class="text-right">
            <span v-if="selectedSubmission.status === 'late'" class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Late Submission</span>
            <span v-else class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">On Time</span>
          </div>
        </div>

        <div class="bg-gray-50 dark:bg-[#1f1f1e] border border-gray-200 dark:border-[#3e3e3a] rounded p-4 mb-6 text-left">
          <h4 class="font-semibold text-sm mb-2 text-gray-900 dark:text-[#ededec]">Submitted Response</h4>
          <p class="text-sm text-gray-700 dark:text-[#ededec] whitespace-pre-wrap mb-4">{{ selectedSubmission.content || '(No text submitted)' }}</p>
          
          <div v-if="selectedSubmission.file_path" class="text-xs text-gray-500 dark:text-[#a1a09a]">
            <span>Attached File: </span>
            <a :href="'/storage/' + selectedSubmission.file_path" target="_blank" class="text-[#f53003] dark:text-[#ff4433] underline font-medium">Download / View File</a>
          </div>
        </div>

        <!-- Grade & Feedback form -->
        <div class="border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-5 text-left">
          <h4 class="font-semibold text-gray-900 dark:text-[#ededec] mb-4">Grade & Feedback</h4>
          
          <div v-if="successMessage" class="mb-4 p-3 bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 rounded text-sm">{{ successMessage }}</div>
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 rounded text-sm">{{ errorMessage }}</div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#ededec] mb-1">Score (0 - 100)</label>
            <input v-model.number="score" type="number" min="0" max="100" class="w-full p-2 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec]" placeholder="e.g. 85">
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#ededec] mb-1">Feedback Text</label>
            <textarea v-model="feedback" class="w-full p-2 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec] h-28" placeholder="Enter feedback details..."></textarea>
          </div>

          <button @click="submitGrade" :disabled="submitting" class="px-5 py-2 rounded-md font-semibold text-sm bg-[#1b1b18] dark:bg-[#ededec] text-white dark:text-[#1c1c1a] border border-gray-300 dark:border-[#3e3e3a] hover:opacity-90 transition disabled:opacity-50">
            {{ submitting ? 'Saving...' : 'Save Grade & Feedback' }}
          </button>
        </div>
      </div>
      <div v-else class="h-full flex items-center justify-center text-gray-500 dark:text-[#a1a09a] py-20">
        Select a student submission from the list to begin grading
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    token: { type: String, required: true }
  },
  data() {
    return {
      classrooms: [],
      selectedClassId: null,
      assignments: [],
      selectedAssignmentId: null,
      submissions: [],
      selectedSubmission: null,
      score: null,
      feedback: '',
      submitting: false,
      successMessage: '',
      errorMessage: ''
    }
  },
  watch: {
    token: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${newVal}`;
          this.fetchClassrooms();
        }
      }
    }
  },
  methods: {
    async fetchClassrooms() {
      try {
        const res = await axios.get('/api/classes');
        if (res.data && res.data.success) {
          this.classrooms = res.data.data;
        }
      } catch (err) {
        console.error('Error fetching classrooms:', err);
      }
    },
    async fetchAssignments() {
      this.selectedAssignmentId = null;
      this.submissions = [];
      this.selectedSubmission = null;
      if (!this.selectedClassId) return;

      try {
        const res = await axios.get(`/api/classes/${this.selectedClassId}/assignments`);
        this.assignments = res.data.data || res.data || [];
      } catch (err) {
        console.error('Error fetching assignments:', err);
      }
    },
    async fetchSubmissions() {
      this.selectedSubmission = null;
      if (!this.selectedAssignmentId) return;

      try {
        const res = await axios.get(`/api/submissions?assignment_id=${this.selectedAssignmentId}`);
        if (res.data && res.data.success) {
          this.submissions = res.data.data;
        }
      } catch (err) {
        console.error('Error fetching submissions:', err);
      }
    },
    selectSubmission(sub) {
      this.selectedSubmission = sub;
      this.score = sub.grade;
      this.feedback = sub.feedback || '';
      this.successMessage = '';
      this.errorMessage = '';
    },
    async submitGrade() {
      if (this.score === null || this.score === '') {
        this.errorMessage = 'Please enter a valid score.';
        return;
      }
      this.submitting = true;
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const res = await axios.post('/api/grades', {
          submission_id: this.selectedSubmission.id,
          score: parseInt(this.score),
          feedback: this.feedback
        });
        
        if (res.data && res.data.success) {
          this.successMessage = 'Grade and feedback saved successfully!';
          // Update local submission object in the list
          const updatedGrade = res.data.data;
          this.selectedSubmission.grade = updatedGrade.score;
          this.selectedSubmission.feedback = updatedGrade.feedback;
          
          // Refetch to ensure list is synchronized
          await this.fetchSubmissions();
          // Find and re-select
          const match = this.submissions.find(s => s.id === this.selectedSubmission.id);
          if (match) this.selectedSubmission = match;
        }
      } catch (err) {
        this.errorMessage = err.response?.data?.message || 'Failed to save grade.';
      } finally {
        this.submitting = false;
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
}
</script>
