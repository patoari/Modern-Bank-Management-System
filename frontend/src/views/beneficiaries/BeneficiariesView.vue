<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Beneficiaries</h1>
        <p class="text-gray-600 mt-1">Manage your transfer recipients</p>
      </div>
      <button
        @click="showAddForm = true"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + Add Beneficiary
      </button>
    </div>

    <!-- Add/Edit Form Modal -->
    <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-xl font-bold">{{ editingId ? 'Edit' : 'Add' }} Beneficiary</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input
            v-model="form.beneficiary_name"
            type="text"
            placeholder="Recipient name"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Account Number *</label>
          <input
            v-model="form.account_number"
            type="text"
            placeholder="Account number"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">IFSC Code</label>
            <input
              v-model="form.ifsc_code"
              type="text"
              placeholder="IFSC"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type *</label>
            <select
              v-model="form.account_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="savings">Savings</option>
              <option value="current">Current</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
          <select
            v-model="form.relationship"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="self">Self</option>
            <option value="family">Family</option>
            <option value="business">Business</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
          <input
            v-model="form.bank_name"
            type="text"
            placeholder="Bank name"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 rounded p-3">
          <p class="text-red-700 text-sm">{{ error }}</p>
        </div>

        <div class="flex gap-3 pt-4">
          <button
            @click="showAddForm = false; editingId = null; resetForm()"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="saveBeneficiary"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Beneficiaries List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="beneficiary in beneficiaries"
        :key="beneficiary.id"
        class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition"
      >
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ beneficiary.beneficiary_name }}</h3>
            <p class="text-sm text-gray-600">{{ beneficiary.bank_name || 'Bank' }}</p>
          </div>
          <span
            :class="{
              'bg-green-100 text-green-800': beneficiary.verification_status === 'verified',
              'bg-yellow-100 text-yellow-800': beneficiary.verification_status === 'pending',
              'bg-red-100 text-red-800': beneficiary.verification_status === 'rejected',
            }"
            class="px-2 py-1 rounded text-xs font-medium"
          >
            {{ beneficiary.verification_status }}
          </span>
        </div>

        <div class="space-y-2 text-sm mb-4">
          <p class="text-gray-700">
            <span class="text-gray-600">Account:</span>
            {{ beneficiary.account_number }}
          </p>
          <p class="text-gray-700">
            <span class="text-gray-600">Type:</span>
            {{ beneficiary.account_type }}
          </p>
          <p class="text-gray-700">
            <span class="text-gray-600">Relationship:</span>
            {{ beneficiary.relationship }}
          </p>
        </div>

        <div class="flex gap-2">
          <button
            @click="editBeneficiary(beneficiary)"
            class="flex-1 px-3 py-2 text-sm border border-blue-600 text-blue-600 rounded hover:bg-blue-50"
          >
            Edit
          </button>
          <button
            @click="deleteBeneficiary(beneficiary.id)"
            class="flex-1 px-3 py-2 text-sm border border-red-600 text-red-600 rounded hover:bg-red-50"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="beneficiaries.length === 0 && !loading" class="text-center py-12">
      <p class="text-gray-600">No beneficiaries added yet.</p>
      <button
        @click="showAddForm = true"
        class="text-blue-600 hover:underline mt-2"
      >
        Add your first beneficiary
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">Loading...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const beneficiaries = ref([])
const showAddForm = ref(false)
const editingId = ref(null)
const loading = ref(false)
const error = ref('')

const form = ref({
  beneficiary_name: '',
  account_number: '',
  ifsc_code: '',
  account_type: 'savings',
  relationship: 'family',
  bank_name: '',
})

const resetForm = () => {
  form.value = {
    beneficiary_name: '',
    account_number: '',
    ifsc_code: '',
    account_type: 'savings',
    relationship: 'family',
    bank_name: '',
  }
  error.value = ''
}

onMounted(loadBeneficiaries)

const loadBeneficiaries = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/beneficiaries')
    beneficiaries.value = response.data.data
  } catch (err) {
    toast.error('Failed to load beneficiaries')
  } finally {
    loading.value = false
  }
}

const saveBeneficiary = async () => {
  if (!form.value.beneficiary_name || !form.value.account_number) {
    error.value = 'Please fill in required fields'
    return
  }

  loading.value = true
  error.value = ''

  try {
    if (editingId.value) {
      await axios.patch(`/api/v1/beneficiaries/${editingId.value}`, form.value)
      toast.success('Beneficiary updated successfully')
    } else {
      await axios.post('/api/v1/beneficiaries', form.value)
      toast.success('Beneficiary added successfully')
    }
    showAddForm.value = false
    editingId.value = null
    resetForm()
    loadBeneficiaries()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save beneficiary'
  } finally {
    loading.value = false
  }
}

const editBeneficiary = (beneficiary) => {
  form.value = { ...beneficiary }
  editingId.value = beneficiary.id
  showAddForm.value = true
}

const deleteBeneficiary = async (id) => {
  if (confirm('Are you sure you want to delete this beneficiary?')) {
    try {
      await axios.delete(`/api/v1/beneficiaries/${id}`)
      toast.success('Beneficiary deleted successfully')
      loadBeneficiaries()
    } catch (err) {
      toast.error('Failed to delete beneficiary')
    }
  }
}
</script>
