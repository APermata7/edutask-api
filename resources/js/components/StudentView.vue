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

      <div v-if="selectedClassId" class="flex-1 flex flex-col overflow-hidden">
        <label class="block text-xs font-semibold text-gray-500 dark:text-[#a1a09a] uppercase mb-2">Your Assignments</label>
        <div class="flex-1 overflow-y-auto max-h-[350px] flex flex-col gap-2">
          <div v-for="a in assignments" :key="a.id" @click="selectAssignment(a)" :class="['p-3 rounded-lg border cursor-pointer transition text-left', selectedAssignment && selectedAssignment.id === a.id ? 'bg-[#fff2f2] dark:bg-[#2d100c] border-[#f53003] dark:border-[#ff4433]' : 'border-gray-200 dark:border-[#3e3e3a] hover:bg-gray-100 dark:hover:bg-[#262624]']">
            <div class="font-medium text-gray-900 dark:text-[#ededec]">{{ a.title }}</div>
            <div class="text-xs text-gray-500 dark:text-[#a1a09a] flex items-center gap-2 mt-1">
              <span>Due: {{ formatDate(a.due_at) }}</span>
              <span v-if="a.user_submission" class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                {{ a.user_submission.grade !== null ? 'Graded: ' + a.user_submission.grade : 'Submitted' }}
              </span>
              <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-[#ededec]">Not Submitted</span>
            </div>
          </div>
          <div v-if="assignments.length === 0" class="text-sm text-gray-500 dark:text-[#a1a09a] py-4 text-center">No assignments in this class</div>
        </div>
      </div>
    </div>

    <!-- Right Column: Detail / Workspace -->
    <div class="bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-6">
      <div v-if="selectedAssignment">
        <div class="flex justify-between items-start border-b border-gray-200 dark:border-[#3e3e3a] pb-4 mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-[#ededec]">{{ selectedAssignment.title }}</h3>
            <p class="text-sm text-gray-500 dark:text-[#a1a09a]">Due Date: {{ formatDate(selectedAssignment.due_at) }}</p>
          </div>
          <div v-if="submission" class="text-right">
            <span v-if="submission.grade !== null" class="badge badge-graded" style="font-size: 15px; padding: 6px 12px; margin-left: 0;">Grade: {{ submission.grade }} / 100</span>
            <span v-else-if="submission.status === 'late'" class="badge badge-late" style="font-size: 13px; padding: 4px 8px; margin-left: 0;">Late</span>
            <span v-else class="badge badge-submitted" style="font-size: 13px; padding: 4px 8px; margin-left: 0;">Submitted</span>
          </div>
        </div>

        <div class="bg-gray-50 dark:bg-[#1f1f1e] border border-gray-200 dark:border-[#3e3e3a] rounded p-4 mb-6 text-left">
          <h4 class="font-semibold text-sm mb-2 text-gray-900 dark:text-[#ededec]">Assignment Instructions</h4>
          <p class="text-sm text-gray-700 dark:text-[#ededec] whitespace-pre-wrap mb-4">{{ selectedAssignment.description || 'No description provided.' }}</p>
          <p v-if="selectedAssignment.instructions" class="text-xs text-gray-500 dark:text-[#a1a09a] italic">Instructions: {{ selectedAssignment.instructions }}</p>
        </div>

        <!-- Grade & Feedback display if graded -->
        <div v-if="submission && submission.grade !== null" class="submission-content-box border-l-4 border-green-600 p-5 text-left mb-6">
          <h4 class="font-semibold text-gray-900 dark:text-[#ededec] mb-2">Lecturer Grade & Feedback</h4>
          <div class="text-2xl font-bold text-green-700 dark:text-green-400 mb-2">Score: {{ submission.grade }} / 100</div>
          <p class="text-sm text-gray-700 dark:text-[#ededec] italic mb-2">"{{ submission.feedback || 'No feedback comment provided.' }}"</p>
          <div class="text-[10px] text-gray-400">Graded by Lecturer</div>
        </div>

        <!-- Submission / Resubmission Form -->
        <div class="border border-gray-200 dark:border-[#3e3e3a] rounded-lg p-5 text-left">
          <h4 class="font-semibold text-gray-900 dark:text-[#ededec] mb-4">
            {{ submission ? 'Your Submission (Update / Revise)' : 'Submit Your Assignment' }}
          </h4>
          
          <div v-if="successMessage" class="mb-4 p-3 bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 rounded text-sm">{{ successMessage }}</div>
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 rounded text-sm">{{ errorMessage }}</div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#ededec] mb-1">Repository Link or Work Details</label>
            <input v-model="content" type="text" class="w-full p-2 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec]" placeholder="e.g. Link to github repository or short notes">
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#ededec] mb-1">Upload File (Optional - PDF, ZIP, Image up to 5MB)</label>
            <input @change="handleFileUpload" type="file" class="w-full p-1.5 border border-gray-300 dark:border-[#3e3e3a] rounded bg-white dark:bg-[#161615] text-gray-900 dark:text-[#ededec]">
            <div v-if="submission && submission.file_path" class="text-xs text-gray-500 dark:text-[#a1a09a] mt-2">
              <span>Currently attached: </span>
              <a :href="'/storage/' + submission.file_path" target="_blank" class="text-[#f53003] dark:text-[#ff4433] underline">View Current File</a>
            </div>
          </div>

          <button @click="submitWork" :disabled="submitting" class="px-5 py-2 rounded-md font-semibold text-sm bg-[#1b1b18] dark:bg-[#ededec] text-white dark:text-[#1c1c1a] border border-gray-300 dark:border-[#3e3e3a] hover:opacity-90 transition disabled:opacity-50">
            {{ submitting ? 'Submitting...' : (submission ? 'Resubmit (Update Submission)' : 'Submit Work') }}
          </button>
        </div>
      </div>
      <div v-else class="h-full flex items-center justify-center text-gray-500 dark:text-[#a1a09a] py-20">
        Select an assignment from the list to view instructions or submit your work
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
      selectedAssignment: null,
      submission: null,
      content: '',
      file: null,
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
      this.selectedAssignment = null;
      this.submission = null;
      if (!this.selectedClassId) return;

      try {
        const res = await axios.get(`/api/classes/${this.selectedClassId}/assignments`);
        const data = res.data.data || res.data || [];
        this.assignments = data;
        
        // Loop through assignments to load submission status
        for (let a of this.assignments) {
          try {
            const subRes = await axios.get(`/api/submissions?assignment_id=${a.id}`);
            if (subRes.data && subRes.data.success && subRes.data.data.length > 0) {
              a.user_submission = subRes.data.data[0];
            } else {
              a.user_submission = null;
            }
          } catch (e) {
            a.user_submission = null;
          }
        }
      } catch (err) {
        console.error('Error fetching assignments:', err);
      }
    },
    async selectAssignment(assignment) {
      this.selectedAssignment = assignment;
      this.successMessage = '';
      this.errorMessage = '';
      this.content = '';
      this.file = null;
      
      try {
        const res = await axios.get(`/api/submissions?assignment_id=${assignment.id}`);
        if (res.data && res.data.success && res.data.data.length > 0) {
          this.submission = res.data.data[0];
          this.content = this.submission.content || '';
        } else {
          this.submission = null;
        }
      } catch (err) {
        console.error('Error fetching submission:', err);
        this.submission = null;
      }
    },
    handleFileUpload(event) {
      this.file = event.target.files[0];
    },
    async submitWork() {
      this.submitting = true;
      this.successMessage = '';
      this.errorMessage = '';

      const formData = new FormData();
      formData.append('content', this.content);
      if (this.file) {
        formData.append('file', this.file);
      }

      try {
        let res;
        if (this.submission) {
          // Resubmit / Update submission using POST with _method = PUT (due to Laravel multipart limitation)
          formData.append('_method', 'PUT');
          res = await axios.post(`/api/submissions/${this.submission.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          if (res.data && res.data.success) {
            this.successMessage = 'Submission revised successfully!';
            this.submission = res.data.data;
          }
        } else {
          // Initial submission
          formData.append('assignment_id', this.selectedAssignment.id);
          res = await axios.post('/api/submissions', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          if (res.data && res.data.success) {
            this.successMessage = 'Tugas berhasil dikumpulkan!';
            this.submission = res.data.data;
          }
        }
        
        // Refresh assignments to update badges
        await this.fetchAssignments();
        // Re-select assignment reference
        const match = this.assignments.find(a => a.id === this.selectedAssignment.id);
        if (match) this.selectedAssignment = match;
      } catch (err) {
        this.errorMessage = err.response?.data?.message || err.response?.data?.errors?.file?.[0] || 'Failed to submit work.';
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
