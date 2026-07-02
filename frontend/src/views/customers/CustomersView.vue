<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">Manage customer profiles, KYC, and accounts</p>
      </div>
      <button class="btn btn-primary" @click="openAddModal">+ Add Customer</button>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-body" style="padding:16px">
        <div class="filters">
          <input v-model="filters.search" type="text" class="form-control" placeholder="Search by name, CIF, email..." style="flex:1" />
          <select v-model="filters.segment" class="form-control" style="width:160px">
            <option value="">All Segments</option>
            <option v-for="s in segments" :key="s" :value="s">{{ capitalize(s) }}</option>
          </select>
          <select v-model="filters.kyc_status" class="form-control" style="width:160px">
            <option value="">KYC Status</option>
            <option v-for="k in kycStatuses" :key="k" :value="k">{{ capitalize(k) }}</option>
          </select>
          <select v-model="filters.risk_rating" class="form-control" style="width:140px">
            <option value="">Risk Rating</option>
            <option v-for="r in risks" :key="r" :value="r">{{ capitalize(r) }}</option>
          </select>
          <button class="btn btn-ghost" @click="resetFilters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">{{ filteredCustomers.length }} Customers</span>
        <button class="btn btn-ghost btn-sm" @click="exportCustomers">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>CIF / Customer</th>
              <th>Segment</th>
              <th>Contact</th>
              <th>KYC Status</th>
              <th>Risk</th>
              <th>AML</th>
              <th>Since</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in paginatedCustomers" :key="c.id">
              <td>
                <div class="flex items-center gap-2">
                  <div class="avatar">{{ initials(c) }}</div>
                  <div>
                    <p class="font-bold" style="font-size:.875rem">{{ c.first_name }} {{ c.last_name }}</p>
                    <p class="text-muted text-xs">{{ c.customer_id }}</p>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-primary">{{ capitalize(c.segment) }}</span></td>
              <td>
                <p style="font-size:.82rem">{{ c.email }}</p>
                <p class="text-muted text-xs">{{ c.phone }}</p>
              </td>
              <td><span class="badge" :class="kycBadge(c.kyc_status)">{{ c.kyc_status }}</span></td>
              <td><span class="badge" :class="riskBadge(c.risk_rating)">{{ c.risk_rating }}</span></td>
              <td><span class="badge" :class="c.aml_status === 'clear' ? 'badge-success' : 'badge-danger'">{{ c.aml_status }}</span></td>
              <td class="text-muted text-sm">{{ c.customer_since }}</td>
              <td>
                <div class="flex gap-1">
                  <router-link :to="`/customers/${c.id}`" class="btn btn-ghost btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                  </router-link>
                  <button class="btn btn-ghost btn-sm" @click="editCustomer(c)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!filteredCustomers.length" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--gray-300);margin:0 auto 12px;display:block"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
          <p>No customers found matching your criteria.</p>
        </div>
      </div>
      <!-- Pagination -->
      <div class="pagination">
        <span class="text-muted text-sm" style="flex:1">
          Showing {{ (page-1)*pageSize + 1 }}–{{ Math.min(page*pageSize, filteredCustomers.length) }} of {{ filteredCustomers.length }}
        </span>
        <button class="page-btn" :disabled="page <= 1" @click="page--">‹</button>
        <button v-for="p in totalPages" :key="p" class="page-btn" :class="{ active: p === page }" @click="page = p">{{ p }}</button>
        <button class="page-btn" :disabled="page >= totalPages" @click="page++">›</button>
      </div>
    </div>

    <!-- Add/Edit Customer Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">{{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">First Name *</label>
                  <input v-model="form.first_name" type="text" class="form-control" placeholder="John" />
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name *</label>
                  <input v-model="form.last_name" type="text" class="form-control" placeholder="Smith" />
                </div>
                <div class="form-group">
                  <label class="form-label">Email *</label>
                  <input v-model="form.email" type="email" class="form-control" placeholder="john@email.com" />
                </div>
                <div class="form-group">
                  <label class="form-label">Phone</label>
                  <input v-model="form.phone" type="tel" class="form-control" placeholder="+1 234 567 890" />
                </div>
                <div class="form-group">
                  <label class="form-label">Customer Type *</label>
                  <select v-model="form.customer_type" class="form-control">
                    <option value="individual">Individual</option>
                    <option value="business">Business</option>
                    <option value="corporate">Corporate</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Segment</label>
                  <select v-model="form.segment" class="form-control">
                    <option v-for="s in segments" :key="s" :value="s">{{ capitalize(s) }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date of Birth</label>
                  <input v-model="form.date_of_birth" type="date" class="form-control" />
                </div>
                <div class="form-group">
                  <label class="form-label">Risk Rating</label>
                  <select v-model="form.risk_rating" class="form-control">
                    <option v-for="r in risks" :key="r" :value="r">{{ capitalize(r) }}</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showModal = false">Cancel</button>
              <button class="btn btn-primary" @click="saveCustomer">{{ editingCustomer ? 'Update' : 'Create Customer' }}</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useRoute } from 'vue-router'

const toast = useToast()
const route = useRoute()
const showModal = ref(false)
const editingCustomer = ref(null)
const page = ref(1)
const pageSize = 10

const filters = reactive({ search: '', segment: '', kyc_status: '', risk_rating: '' })

// Read search query from topbar navigation
onMounted(() => {
  if (route.query.search) filters.search = String(route.query.search)
})
watch(() => route.query.search, val => {
  if (val) filters.search = String(val)
})
const form    = reactive({ first_name:'', last_name:'', email:'', phone:'', customer_type:'individual', segment:'retail', date_of_birth:'', risk_rating:'low' })

const segments   = ['retail','sme','corporate','hni','premier']
const kycStatuses= ['pending','under_review','approved','rejected','expired']
const risks      = ['low','medium','high','very_high']

// Mock data
const customers = ref([
  { id:1, customer_id:'CIF-001', first_name:'Alice', last_name:'Johnson',  email:'alice@example.com', phone:'+1-202-555-0101', segment:'hni',     kyc_status:'approved',    risk_rating:'low',     aml_status:'clear',   customer_since:'2020-03-15' },
  { id:2, customer_id:'CIF-002', first_name:'Bob',   last_name:'Williams', email:'bob@example.com',   phone:'+1-202-555-0102', segment:'retail',   kyc_status:'approved',    risk_rating:'medium',  aml_status:'clear',   customer_since:'2021-07-22' },
  { id:3, customer_id:'CIF-003', first_name:'Carol', last_name:'Davis',    email:'carol@example.com', phone:'+1-202-555-0103', segment:'sme',      kyc_status:'under_review',risk_rating:'medium',  aml_status:'flagged', customer_since:'2022-01-10' },
  { id:4, customer_id:'CIF-004', first_name:'David', last_name:'Martinez', email:'david@example.com', phone:'+1-202-555-0104', segment:'corporate',kyc_status:'approved',    risk_rating:'low',     aml_status:'clear',   customer_since:'2019-11-05' },
  { id:5, customer_id:'CIF-005', first_name:'Eva',   last_name:'Garcia',   email:'eva@example.com',   phone:'+1-202-555-0105', segment:'premier',  kyc_status:'approved',    risk_rating:'low',     aml_status:'clear',   customer_since:'2018-06-30' },
  { id:6, customer_id:'CIF-006', first_name:'Frank', last_name:'Lee',      email:'frank@example.com', phone:'+1-202-555-0106', segment:'retail',   kyc_status:'pending',     risk_rating:'high',    aml_status:'clear',   customer_since:'2023-04-18' },
  { id:7, customer_id:'CIF-007', first_name:'Grace', last_name:'Wilson',   email:'grace@example.com', phone:'+1-202-555-0107', segment:'retail',   kyc_status:'approved',    risk_rating:'low',     aml_status:'clear',   customer_since:'2022-09-01' },
])

const filteredCustomers = computed(() => {
  return customers.value.filter(c => {
    const q = filters.search.toLowerCase()
    const matchSearch = !q || `${c.first_name} ${c.last_name} ${c.customer_id} ${c.email}`.toLowerCase().includes(q)
    const matchSegment = !filters.segment || c.segment === filters.segment
    const matchKyc     = !filters.kyc_status || c.kyc_status === filters.kyc_status
    const matchRisk    = !filters.risk_rating || c.risk_rating === filters.risk_rating
    return matchSearch && matchSegment && matchKyc && matchRisk
  })
})

const totalPages       = computed(() => Math.max(1, Math.ceil(filteredCustomers.value.length / pageSize)))
const paginatedCustomers = computed(() => filteredCustomers.value.slice((page.value-1)*pageSize, page.value*pageSize))

function resetFilters() { filters.search = ''; filters.segment = ''; filters.kyc_status = ''; filters.risk_rating = ''; page.value = 1 }
function capitalize(s)  { return s?.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase()) }
function initials(c)    { return (c.first_name[0] + c.last_name[0]).toUpperCase() }

function kycBadge(s)  { return { approved:'badge-success', pending:'badge-warning', under_review:'badge-info', rejected:'badge-danger', expired:'badge-gray' }[s] ?? 'badge-gray' }
function riskBadge(r) { return { low:'badge-success', medium:'badge-warning', high:'badge-danger', very_high:'badge-danger' }[r] ?? 'badge-gray' }

function openAddModal() {
  editingCustomer.value = null
  Object.assign(form, { first_name:'', last_name:'', email:'', phone:'', customer_type:'individual', segment:'retail', date_of_birth:'', risk_rating:'low' })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingCustomer.value = null
}

function editCustomer(c) {
  editingCustomer.value = c
  Object.assign(form, { first_name: c.first_name, last_name: c.last_name, email: c.email, phone: c.phone, customer_type: c.customer_type || 'individual', segment: c.segment, date_of_birth: c.date_of_birth || '', risk_rating: c.risk_rating })
  showModal.value = true
}

function saveCustomer() {
  if (!form.first_name || !form.last_name || !form.email) {
    toast.error('Please fill in required fields.')
    return
  }
  if (editingCustomer.value) {
    const idx = customers.value.findIndex(c => c.id === editingCustomer.value.id)
    if (idx !== -1) Object.assign(customers.value[idx], { first_name: form.first_name, last_name: form.last_name, email: form.email, phone: form.phone, segment: form.segment, risk_rating: form.risk_rating })
    toast.success('Customer updated successfully.')
  } else {
    customers.value.push({
      id: Date.now(),
      customer_id: `CIF-${String(customers.value.length + 1).padStart(3,'0')}`,
      ...form,
      kyc_status:      'pending',
      aml_status:      'clear',
      customer_since:  new Date().toISOString().split('T')[0],
    })
    toast.success('Customer created successfully.')
  }
  closeModal()
}

function exportCustomers() {
  const rows = [
    ['CIF','First Name','Last Name','Email','Phone','Segment','KYC Status','Risk Rating','AML Status','Since'],
    ...filteredCustomers.value.map(c => [
      c.customer_id, c.first_name, c.last_name, c.email, c.phone,
      c.segment, c.kyc_status, c.risk_rating, c.aml_status, c.customer_since,
    ])
  ]
  const csv  = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type:'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `customers-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.success('Customer list exported.')
}
</script>

<style scoped>
.filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700;
  flex-shrink: 0;
}
</style>
