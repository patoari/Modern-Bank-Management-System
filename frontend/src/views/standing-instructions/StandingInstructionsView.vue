<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Standing Instructions</h1>
        <p class="text-gray-600 mt-1">Set up automatic recurring transfers</p>
      </div>
      <button
        @click="showAddForm = true"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + New Instruction
      </button>
    </div>

    <!-- Add/Edit Form Modal -->
    <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 space-y-4 max-h-screen overflow-y-auto">
        <h2 class="text-xl font-bold sticky top-0 bg-white">New Standing Instruction</h2>

        <!-- From Account -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">From Account *</label>
          <select
            v-model="form.from_account_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select account</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
              {{ account.account_title }} - {{ account.account_number }}
            </option>
          </select>
        </div>

        <!-- Recipient Options -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To Account</label>
            <select
              v-model="form.to_account_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select account</option>
              <option v-for="account in accounts" :key="account.id" :value="account.id">
                {{ account.account_title }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Beneficiary</label>
            <select
              v-model="form.beneficiary_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select beneficiary</option>
              <option v-for="bene in beneficiaries" :key="bene.id" :value="bene.id">
                {{ bene.beneficiary_name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Details -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Instruction Name</label>
          <input
            v-model="form.instruction_name"
            type="text"
            placeholder="e.g., Monthly rent payment"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input
              v-model.number="form.amount"
              type="number"
              placeholder="0.00"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Frequency *</label>
            <select
              v-model="form.frequency"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="fortnightly">Fortnightly</option>
              <option value="monthly" selected>Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="annually">Annually</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
            <input
              v-model="form.end_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
          <textarea
            v-model="form.remarks"
            rows="2"
            placeholder="Additional notes"
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
            @click="saveInstruction"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Saving...' : 'Create' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Instructions List -->
    <div class="space-y-4">
      <div
        v-for="instruction in instructions"
        :key="instruction.id"
        class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition"
      >
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">
              {{ instruction.instruction_name || 'Standing Instruction' }}
            </h3>
            <p class="text-sm text-gray-600">
              From: {{ instruction.from_account?.account_title }}
            </p>
          </div>
          <span
            :class="{
              'bg-green-100 text-green-800': instruction.status === 'active',
              'bg-yellow-100 text-yellow-800': instruction.status === 'suspended',
              'bg-gray-100 text-gray-800': instruction.status === 'completed',
              'bg-red-100 text-red-800': instruction.status === 'cancelled',
            }"
            class="px-2 py-1 rounded text-xs font-medium"
          >
            {{ instruction.status }}
          </span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
          <div>
            <p class="text-gray-600">Amount</p>
            <p class="font-semibold">${{ instruction.amount }}</p>
          </div>
          <div>
            <p class="text-gray-600">Frequency</p>
            <p class="font-semibold">{{ instruction.frequency }}</p>
          </div>
          <div>
            <p class="text-gray-600">Executions</p>
            <p class="font-semibold">{{ instruction.executed_count }}</p>
          </div>
          <div>
            <p class="text-gray-600">Next</p>
            <p class="font-semibold">{{ formatDate(instruction.next_execution_at) }}</p>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            v-if="instruction.status === 'active'"
            @click="pauseInstruction(instruction.id)"
            class="px-3 py-2 text-sm border border-yellow-600 text-yellow-600 rounded hover:bg-yellow-50"
          >
            Pause
          </button>
          <button
            v-if="instruction.status === 'suspended'"
            @click="resumeInstruction(instruction.id)"
            class="px-3 py-2 text-sm border border-green-600 text-green-600 rounded hover:bg-green-50"
          >
            Resume
          </button>
          <button
            @click="viewHistory(instruction.id)"
            class="px-3 py-2 text-sm border border-blue-600 text-blue-600 rounded hover:bg-blue-50"
          >
            History
          </button>
          <button
            @click="deleteInstruction(instruction.id)"
            class="px-3 py-2 text-sm border border-red-600 text-red-600 rounded hover:bg-red-50"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="instructions.length === 0 && !loading" class="text-center py-12">
      <p class="text-gray-600">No standing instructions set up yet.</p>
      <button
        @click="showAddForm = true"
        class="text-blue-600 hover:underline mt-2"
      >
        Create your first instruction
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const instructions = ref([])
const accounts = ref([])
const beneficiaries = ref([])
const showAddForm = ref(false)
const loading = ref(false)
const error = ref('')

const form = ref({
  from_account_id: '',
  to_account_id: '',
  beneficiary_id: '',
  instruction_name: '',
  amount: '',
  frequency: 'monthly',
  start_date: '',
  end_date: '',
  remarks: '',
})

const resetForm = () => {
  form.value = {
    from_account_id: '',
    to_account_id: '',
    beneficiary_id: '',
    instruction_name: '',
    amount: '',
    frequency: 'monthly',
    start_date: '',
    end_date: '',
    remarks: '',
  }
  error.value = ''
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadInstructions()
  loadAccounts()
  loadBeneficiaries()
})

const loadInstructions = async () => {
  try {
    const response = await axios.get('/api/v1/standing-instructions')
    instructions.value = response.data.data
  } catch (err) {
    toast.error('Failed to load instructions')
  }
}

const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/v1/accounts')
    accounts.value = response.data.data
  } catch (err) {
    // ignore
  }
}

const loadBeneficiaries = async () => {
  try {
    const response = await axios.get('/api/v1/beneficiaries')
    beneficiaries.value = response.data.data
  } catch (err) {
    // ignore
  }
}

const saveInstruction = async () => {
  if (!form.value.from_account_id || !form.value.amount) {
    error.value = 'Please fill in required fields'
    return
  }

  loading.value = true
  error.value = ''

  try {
    await axios.post('/api/v1/standing-instructions', form.value)
    toast.success('Standing instruction created successfully')
    showAddForm.value = false
    resetForm()
    loadInstructions()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create instruction'
  } finally {
    loading.value = false
  }
}

const pauseInstruction = async (id) => {
  try {
    await axios.post(`/api/v1/standing-instructions/${id}/pause`)
    toast.success('Instruction paused')
    loadInstructions()
  } catch (err) {
    toast.error('Failed to pause instruction')
  }
}

const resumeInstruction = async (id) => {
  try {
    await axios.post(`/api/v1/standing-instructions/${id}/resume`)
    toast.success('Instruction resumed')
    loadInstructions()
  } catch (err) {
    toast.error('Failed to resume instruction')
  }
}

const viewHistory = (id) => {
  toast.info('History view coming soon')
}

const deleteInstruction = async (id) => {
  if (confirm('Are you sure you want to cancel this instruction?')) {
    try {
      await axios.delete(`/api/v1/standing-instructions/${id}`)
      toast.success('Instruction cancelled')
      loadInstructions()
    } catch (err) {
      toast.error('Failed to cancel instruction')
    }
  }
}
</script>
