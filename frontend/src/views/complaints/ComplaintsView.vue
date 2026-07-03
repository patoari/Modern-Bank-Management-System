<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Customer Complaints</h1>
        <p class="text-gray-600 mt-1">Register and track your grievances</p>
      </div>
      <button
        @click="showAddForm = true"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + Register Complaint
      </button>
    </div>

    <!-- Add Form Modal -->
    <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-xl font-bold">Register a Complaint</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
          <input
            v-model="form.complaint_category"
            type="text"
            placeholder="e.g., Account, Card, Transaction"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
          <select
            v-model="form.complaint_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select type</option>
            <option value="service_failure">Service Failure</option>
            <option value="incorrect_transaction">Incorrect Transaction</option>
            <option value="lost_card">Lost Card</option>
            <option value="documentation">Documentation</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select
            v-model="form.priority"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="Describe your complaint in detail"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 rounded p-3">
          <p class="text-red-700 text-sm">{{ error }}</p>
        </div>

        <div class="flex gap-3 pt-4">
          <button
            @click="showAddForm = false; resetForm()"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="submitComplaint"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Submitting...' : 'Submit' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-gray-600 text-sm">Total Complaints</p>
        <p class="text-2xl font-bold text-gray-900">{{ statistics.total_complaints }}</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-gray-600 text-sm">Open</p>
        <p class="text-2xl font-bold text-orange-600">{{ statistics.open_complaints }}</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-gray-600 text-sm">Resolved</p>
        <p class="text-2xl font-bold text-green-600">{{ statistics.resolved_complaints }}</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-gray-600 text-sm">Avg Resolution</p>
        <p class="text-2xl font-bold text-blue-600">{{ statistics.average_resolution_days }} days</p>
      </div>
    </div>

    <!-- Complaints List -->
    <div class="space-y-4">
      <div
        v-for="complaint in complaints"
        :key="complaint.id"
        class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition cursor-pointer"
        @click="expandedComplaint = expandedComplaint === complaint.id ? null : complaint.id"
      >
        <div class="flex justify-between items-start mb-2">
          <div>
            <div class="flex items-center gap-3">
              <h3 class="font-semibold text-gray-900">{{ complaint.complaint_ref }}</h3>
              <span
                :class="{
                  'bg-orange-100 text-orange-800': complaint.status === 'open',
                  'bg-blue-100 text-blue-800': complaint.status === 'acknowledged',
                  'bg-yellow-100 text-yellow-800': complaint.status === 'under_investigation',
                  'bg-green-100 text-green-800': complaint.status === 'resolved',
                  'bg-gray-100 text-gray-800': complaint.status === 'closed',
                }"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ complaint.status }}
              </span>
              <span
                :class="{
                  'bg-red-100 text-red-800': complaint.priority === 'critical',
                  'bg-orange-100 text-orange-800': complaint.priority === 'high',
                  'bg-yellow-100 text-yellow-800': complaint.priority === 'medium',
                  'bg-blue-100 text-blue-800': complaint.priority === 'low',
                }"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ complaint.priority }}
              </span>
            </div>
            <p class="text-sm text-gray-600 mt-1">{{ complaint.complaint_category }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500">Registered</p>
            <p class="text-sm font-semibold">{{ formatDate(complaint.registered_date) }}</p>
          </div>
        </div>

        <p class="text-gray-700 text-sm mb-3 line-clamp-2">{{ complaint.description }}</p>

        <!-- Expanded Details -->
        <div v-if="expandedComplaint === complaint.id" class="mt-4 pt-4 border-t border-gray-200">
          <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-gray-600">Type</p>
                <p class="font-semibold">{{ complaint.complaint_type }}</p>
              </div>
              <div>
                <p class="text-gray-600">Target Resolution</p>
                <p class="font-semibold">{{ formatDate(complaint.target_resolution_date) }}</p>
              </div>
            </div>

            <div v-if="complaint.resolution_notes" class="bg-blue-50 rounded p-3">
              <p class="text-gray-600 text-xs mb-1">Resolution Notes</p>
              <p class="text-gray-900">{{ complaint.resolution_notes }}</p>
            </div>

            <button
              @click="addRemark(complaint)"
              class="w-full px-3 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 text-sm font-medium"
            >
              Add Remark
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="complaints.length === 0 && !loading" class="text-center py-12">
      <p class="text-gray-600">No complaints registered yet.</p>
      <button
        @click="showAddForm = true"
        class="text-blue-600 hover:underline mt-2"
      >
        Register your first complaint
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const complaints = ref([])
const statistics = ref({
  total_complaints: 0,
  open_complaints: 0,
  resolved_complaints: 0,
  closed_complaints: 0,
  average_resolution_days: 0,
})
const showAddForm = ref(false)
const expandedComplaint = ref(null)
const loading = ref(false)
const error = ref('')

const form = ref({
  complaint_category: '',
  complaint_type: '',
  priority: 'medium',
  description: '',
})

const resetForm = () => {
  form.value = {
    complaint_category: '',
    complaint_type: '',
    priority: 'medium',
    description: '',
  }
  error.value = ''
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadComplaints()
  loadStatistics()
})

const loadComplaints = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/complaints')
    complaints.value = response.data.data
  } catch (err) {
    toast.error('Failed to load complaints')
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await axios.get('/api/v1/complaints/statistics')
    statistics.value = response.data.data
  } catch (err) {
    // ignore
  }
}

const submitComplaint = async () => {
  if (!form.value.complaint_category || !form.value.complaint_type || !form.value.description) {
    error.value = 'Please fill in all required fields'
    return
  }

  loading.value = true
  error.value = ''

  try {
    await axios.post('/api/v1/complaints', form.value)
    toast.success('Complaint registered successfully')
    showAddForm.value = false
    resetForm()
    loadComplaints()
    loadStatistics()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to register complaint'
  } finally {
    loading.value = false
  }
}

const addRemark = (complaint) => {
  const remark = prompt('Add your remark:')
  if (remark) {
    submitRemark(complaint.id, remark)
  }
}

const submitRemark = async (complaintId, remarks) => {
  try {
    await axios.patch(`/api/v1/complaints/${complaintId}`, { remarks })
    toast.success('Remark added successfully')
    loadComplaints()
  } catch (err) {
    toast.error('Failed to add remark')
  }
}
</script>
