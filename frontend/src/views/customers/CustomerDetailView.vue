<template>
  <div>
    <div class="page-header">
      <div class="flex items-center gap-3">
        <router-link to="/customers" class="btn btn-ghost btn-sm">← Back</router-link>
        <div>
          <h1 class="page-title">{{ customer.first_name }} {{ customer.last_name }}</h1>
          <p class="page-subtitle">CIF: {{ customer.customer_id }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <span class="badge" :class="kycBadge(customer.kyc_status)">{{ customer.kyc_status }}</span>
        <button class="btn btn-ghost btn-sm" @click="showEditModal = true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit
        </button>
      </div>
    </div>

    <div class="detail-grid">
      <!-- Profile Card -->
      <div class="card">
        <div class="card-header"><span class="card-title">Profile</span></div>
        <div class="card-body">
          <div class="profile-avatar">{{ initials }}</div>
          <div class="detail-rows">
            <div class="detail-row" v-for="row in profileRows" :key="row.label">
              <span class="detail-label">{{ row.label }}</span>
              <span class="detail-value">{{ row.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Accounts -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Accounts</span>
          <button class="btn btn-primary btn-sm" @click="showAccModal = true">+ Open Account</button>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Account #</th><th>Type</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="acc in accounts" :key="acc.number">
                <td class="mono text-sm">{{ acc.number }}</td>
                <td>{{ acc.type }}</td>
                <td class="font-bold">{{ acc.balance }}</td>
                <td><span class="badge" :class="acc.status === 'active' ? 'badge-success' : 'badge-gray'">{{ acc.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Transactions</span>
          <router-link to="/transactions" class="btn btn-ghost btn-sm">View All</router-link>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Ref</th><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
              <tr v-for="t in transactions" :key="t.ref">
                <td class="mono text-xs">{{ t.ref }}</td>
                <td>{{ t.type }}</td>
                <td :class="t.amount > 0 ? 'text-success' : 'text-danger'">
                  {{ t.amount > 0 ? '+' : '' }}${{ Math.abs(t.amount).toLocaleString() }}
                </td>
                <td class="text-muted text-sm">{{ t.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- KYC Documents -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">KYC Documents</span>
          <button class="btn btn-ghost btn-sm" @click="handleUpload">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload
          </button>
          <input ref="fileInput" type="file" style="display:none" accept=".pdf,.jpg,.png" @change="onFileSelected" />
        </div>
        <div class="card-body">
          <div v-for="doc in kycDocs" :key="doc.type" class="doc-row">
            <div>
              <p class="font-bold text-sm">{{ doc.type }}</p>
              <p class="text-muted text-xs">{{ doc.number }}</p>
            </div>
            <span class="badge" :class="doc.status === 'verified' ? 'badge-success' : 'badge-warning'">{{ doc.status }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showEditModal" @click.self="showEditModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Edit Customer</h3>
              <button class="btn btn-icon btn-ghost" @click="showEditModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group"><label class="form-label">First Name</label><input v-model="editForm.first_name" type="text" class="form-control" /></div>
                <div class="form-group"><label class="form-label">Last Name</label><input v-model="editForm.last_name" type="text" class="form-control" /></div>
                <div class="form-group"><label class="form-label">Email</label><input v-model="editForm.email" type="email" class="form-control" /></div>
                <div class="form-group"><label class="form-label">Phone</label><input v-model="editForm.phone" type="tel" class="form-control" /></div>
                <div class="form-group"><label class="form-label">Occupation</label><input v-model="editForm.occupation" type="text" class="form-control" /></div>
                <div class="form-group"><label class="form-label">Annual Income</label><input v-model="editForm.annual_income" type="text" class="form-control" /></div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showEditModal=false">Cancel</button>
              <button class="btn btn-primary" @click="saveEdit">Save Changes</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Open Account Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showAccModal" @click.self="showAccModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Open Account for {{ customer.first_name }}</h3>
              <button class="btn btn-icon btn-ghost" @click="showAccModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Account Type *</label>
                <select v-model="accForm.type" class="form-control">
                  <option value="">Select type</option>
                  <option value="savings">Savings</option><option value="current">Current</option>
                  <option value="salary">Salary</option><option value="nre">NRE</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Currency</label>
                <select v-model="accForm.currency" class="form-control">
                  <option>USD</option><option>EUR</option><option>GBP</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Initial Deposit ($)</label>
                <input v-model="accForm.deposit" type="number" class="form-control" placeholder="0.00" min="0" />
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showAccModal=false">Cancel</button>
              <button class="btn btn-primary" @click="openAccount">Open Account</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'

const route = useRoute()
const toast = useToast()
const showEditModal = ref(false)
const showAccModal  = ref(false)
const fileInput     = ref(null)

const customer = reactive({
  id: route.params.id, customer_id: 'CIF-001',
  first_name: 'Alice', last_name: 'Johnson',
  email: 'alice@example.com', phone: '+1-202-555-0101',
  segment: 'HNI', kyc_status: 'approved', risk_rating: 'low',
  aml_status: 'clear', nationality: 'US', occupation: 'Business Owner',
  annual_income: '$250,000', customer_since: '2020-03-15', gender: 'Female',
})

const editForm = reactive({ first_name: customer.first_name, last_name: customer.last_name, email: customer.email, phone: customer.phone, occupation: customer.occupation, annual_income: customer.annual_income })
const accForm  = reactive({ type:'', currency:'USD', deposit:'' })

const initials = computed(() => (customer.first_name[0] + customer.last_name[0]).toUpperCase())

const profileRows = computed(() => [
  { label:'Email',          value: customer.email },
  { label:'Phone',          value: customer.phone },
  { label:'Nationality',    value: customer.nationality },
  { label:'Occupation',     value: customer.occupation },
  { label:'Annual Income',  value: customer.annual_income },
  { label:'Segment',        value: customer.segment },
  { label:'Risk Rating',    value: customer.risk_rating },
  { label:'AML Status',     value: customer.aml_status },
  { label:'Customer Since', value: customer.customer_since },
])

const accounts = ref([
  { number:'1000-0042-5218', type:'Savings',  balance:'$48,250.00', status:'active' },
  { number:'1000-0042-5219', type:'Current',  balance:'$12,800.00', status:'active' },
])

const transactions = [
  { ref:'TXN-001', type:'Cash Deposit',  amount:  15000, date:'Today' },
  { ref:'TXN-002', type:'Fund Transfer', amount: -8500,  date:'Yesterday' },
  { ref:'TXN-003', type:'Bill Payment',  amount: -1200,  date:'2 days ago' },
]

const kycDocs = ref([
  { type:'National ID',  number:'ID-12345678',    status:'verified' },
  { type:'Utility Bill', number:'Proof of Address',status:'verified' },
  { type:'Photo',        number:'Selfie',          status:'verified' },
])

function kycBadge(s) {
  return { approved:'badge-success', pending:'badge-warning', under_review:'badge-info', rejected:'badge-danger' }[s] ?? 'badge-gray'
}

function saveEdit() {
  Object.assign(customer, { first_name: editForm.first_name, last_name: editForm.last_name, email: editForm.email, phone: editForm.phone, occupation: editForm.occupation, annual_income: editForm.annual_income })
  showEditModal.value = false
  toast.success('Customer profile updated.')
}

function openAccount() {
  if (!accForm.type) { toast.error('Please select an account type.'); return }
  const newAcc = {
    number: '7000-' + String(Math.floor(Math.random()*9000)+1000) + '-' + String(Math.floor(Math.random()*9000)+1000),
    type: accForm.type.charAt(0).toUpperCase() + accForm.type.slice(1),
    balance: `$${parseFloat(accForm.deposit||0).toLocaleString()}.00`,
    status: 'active',
  }
  accounts.value.push(newAcc)
  showAccModal.value = false
  Object.assign(accForm, { type:'', currency:'USD', deposit:'' })
  toast.success(`New ${newAcc.type} account opened — ${newAcc.number}`)
}

function handleUpload() {
  fileInput.value?.click()
}

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (file) {
    kycDocs.value.push({ type: file.name, number: 'Uploaded document', status:'pending' })
    toast.success(`Document "${file.name}" uploaded. Pending verification.`)
    e.target.value = ''
  }
}
</script>

<style scoped>
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.profile-avatar { width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;font-size:1.4rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 20px; }
.detail-rows { display:flex; flex-direction:column; gap:10px; }
.detail-row  { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid var(--gray-100); }
.detail-row:last-child { border-bottom:none; }
.detail-label { font-size:.78rem; color:var(--gray-500); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.detail-value { font-size:.875rem; color:var(--gray-800); font-weight:600; }
.doc-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--gray-100); }
.doc-row:last-child { border-bottom:none; }
.mono { font-family:monospace; }
.text-success { color:var(--success); font-weight:600; }
.text-danger  { color:var(--danger);  font-weight:600; }
@media (max-width:900px) { .detail-grid { grid-template-columns:1fr; } }
</style>
