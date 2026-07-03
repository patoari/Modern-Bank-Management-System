<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Cheque Management</h1>
        <p class="text-gray-600 mt-1">Manage your cheque books and transactions</p>
      </div>
      <button
        @click="showRequestForm = true"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + Request Cheque Book
      </button>
    </div>

    <!-- Request Form Modal -->
    <div v-if="showRequestForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-xl font-bold">Request Cheque Book</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Account *</label>
          <select
            v-model="requestForm.account_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select account</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
              {{ account.account_title }} - {{ account.account_number }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
          <select
            v-model.number="requestForm.quantity"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select quantity</option>
            <option value="10">10 leaves</option>
            <option value="20">20 leaves</option>
            <option value="50">50 leaves</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Mode *</label>
          <select
            v-model="requestForm.delivery_mode"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="counter">Counter Delivery</option>
            <option value="courier">Courier</option>
            <option value="home_delivery">Home Delivery</option>
          </select>
        </div>

        <div v-if="requestForm.delivery_mode === 'home_delivery'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
          <textarea
            v-model="requestForm.delivery_address"
            rows="3"
            placeholder="Your delivery address"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div v-if="requestError" class="bg-red-50 border border-red-200 rounded p-3">
          <p class="text-red-700 text-sm">{{ requestError }}</p>
        </div>

        <div class="flex gap-3 pt-4">
          <button
            @click="showRequestForm = false; requestError = ''"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="requestChequeBook"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Requesting...' : 'Request' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-4 border-b border-gray-200">
      <button
        @click="activeTab = 'books'"
        :class="activeTab === 'books' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
        class="px-4 py-2 font-medium"
      >
        Cheque Books
      </button>
      <button
        @click="activeTab = 'cheques'"
        :class="activeTab === 'cheques' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
        class="px-4 py-2 font-medium"
      >
        Cheques
      </button>
    </div>

    <!-- Cheque Books Tab -->
    <div v-if="activeTab === 'books'" class="space-y-4">
      <div
        v-for="book in chequeBooks"
        :key="book.id"
        class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition"
      >
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ book.cheque_book_number }}</h3>
            <p class="text-sm text-gray-600">{{ book.account?.account_title }}</p>
          </div>
          <span
            :class="{
              'bg-blue-100 text-blue-800': book.status === 'requested',
              'bg-yellow-100 text-yellow-800': book.status === 'issued',
              'bg-green-100 text-green-800': book.status === 'active',
              'bg-gray-100 text-gray-800': book.status === 'inactive',
            }"
            class="px-2 py-1 rounded text-xs font-medium"
          >
            {{ book.status }}
          </span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
          <div>
            <p class="text-gray-600">From Cheque</p>
            <p class="font-semibold">{{ book.from_cheque_no }}</p>
          </div>
          <div>
            <p class="text-gray-600">To Cheque</p>
            <p class="font-semibold">{{ book.to_cheque_no }}</p>
          </div>
          <div>
            <p class="text-gray-600">Delivery Mode</p>
            <p class="font-semibold capitalize">{{ book.delivery_mode }}</p>
          </div>
          <div>
            <p class="text-gray-600">Requested</p>
            <p class="font-semibold">{{ formatDate(book.created_at) }}</p>
          </div>
        </div>
      </div>

      <div v-if="chequeBooks.length === 0" class="text-center py-12">
        <p class="text-gray-600">No cheque books requested yet.</p>
      </div>
    </div>

    <!-- Cheques Tab -->
    <div v-if="activeTab === 'cheques'" class="space-y-4">
      <div class="bg-white rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Cheque #</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Account</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Issued</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="cheque in cheques" :key="cheque.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">{{ cheque.cheque_number }}</td>
              <td class="px-4 py-3">{{ cheque.account?.account_number }}</td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-green-100 text-green-800': cheque.status === 'cleared',
                    'bg-blue-100 text-blue-800': cheque.status === 'issued',
                    'bg-yellow-100 text-yellow-800': cheque.status === 'available',
                    'bg-red-100 text-red-800': ['bounced', 'stopped'].includes(cheque.status),
                  }"
                  class="px-2 py-1 rounded text-xs font-medium"
                >
                  {{ cheque.status }}
                </span>
              </td>
              <td class="px-4 py-3">{{ cheque.issued_at ? formatDate(cheque.issued_at) : '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="cheque.status === 'issued'"
                  @click="showStopForm(cheque)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Stop
                </button>
                <span v-else class="text-gray-500 text-sm">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="cheques.length === 0" class="text-center py-12">
        <p class="text-gray-600">No cheques yet.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const activeTab = ref('books')
const chequeBooks = ref([])
const cheques = ref([])
const accounts = ref([])
const showRequestForm = ref(false)
const loading = ref(false)
const requestError = ref('')

const requestForm = ref({
  account_id: '',
  quantity: '',
  delivery_mode: 'counter',
  delivery_address: '',
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadChequeBooks()
  loadCheques()
  loadAccounts()
})

const loadChequeBooks = async () => {
  try {
    const response = await axios.get('/api/v1/cheque-books')
    chequeBooks.value = response.data.data
  } catch (err) {
    toast.error('Failed to load cheque books')
  }
}

const loadCheques = async () => {
  try {
    const response = await axios.get('/api/v1/cheques')
    cheques.value = response.data.data
  } catch (err) {
    toast.error('Failed to load cheques')
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

const requestChequeBook = async () => {
  if (!requestForm.value.account_id || !requestForm.value.quantity) {
    requestError.value = 'Please fill in required fields'
    return
  }

  loading.value = true
  requestError.value = ''

  try {
    await axios.post('/api/v1/cheque-books/request', requestForm.value)
    toast.success('Cheque book request submitted successfully')
    showRequestForm.value = false
    requestForm.value = { account_id: '', quantity: '', delivery_mode: 'counter', delivery_address: '' }
    loadChequeBooks()
  } catch (err) {
    requestError.value = err.response?.data?.message || 'Failed to request cheque book'
  } finally {
    loading.value = false
  }
}

const showStopForm = (cheque) => {
  const reason = prompt('Enter reason for stopping this cheque:')
  if (reason) {
    stopCheque(cheque.cheque_number, reason)
  }
}

const stopCheque = async (chequeNumber, reason) => {
  try {
    await axios.post('/api/v1/cheques/stop', {
      cheque_number: chequeNumber,
      reason: reason,
    })
    toast.success('Cheque stopped successfully')
    loadCheques()
  } catch (err) {
    toast.error('Failed to stop cheque')
  }
}
</script>
