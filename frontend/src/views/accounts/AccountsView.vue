<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">Accounts</h1>
        <p class="page-subtitle">Manage all bank accounts</p>
      </div>
      <button class="btn btn-primary" @click="showModal = true">+ Open Account</button>
    </div>

    <!-- Stats -->
    <div class="acc-stats">
      <div class="stat-card" v-for="s in stats" :key="s.label">
        <div><p class="stat-label">{{ s.label }}</p><p class="stat-value">{{ s.value }}</p></div>
        <div class="stat-icon" :style="{ background: s.bg }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" :style="{ color: s.iconColor, width:'22px', height:'22px' }">
            <path :d="s.iconPath"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-body" style="padding:14px">
        <div class="flex gap-2 flex-wrap">
          <input v-model="search" type="text" class="form-control" placeholder="Search account number, customer..." style="flex:1;min-width:200px" />
          <select v-model="statusFilter" class="form-control" style="width:150px">
            <option value="">All Statuses</option>
            <option v-for="s in statuses" :key="s" :value="s">{{ capitalize(s) }}</option>
          </select>
          <select v-model="typeFilter" class="form-control" style="width:150px">
            <option value="">All Types</option>
            <option v-for="t in types" :key="t" :value="t">{{ capitalize(t) }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Account Number</th><th>Customer</th><th>Type</th><th>Currency</th>
              <th>Available Balance</th><th>Ledger Balance</th><th>Status</th><th>Opened</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="acc in filtered" :key="acc.id">
              <td><span class="mono">{{ acc.account_number }}</span></td>
              <td>{{ acc.customer }}</td>
              <td><span class="badge badge-primary">{{ capitalize(acc.type) }}</span></td>
              <td>{{ acc.currency }}</td>
              <td class="font-bold">${{ acc.available_balance.toLocaleString() }}</td>
              <td>${{ acc.ledger_balance.toLocaleString() }}</td>
              <td><span class="badge" :class="statusBadge(acc.status)">{{ acc.status }}</span></td>
              <td class="text-muted text-sm">{{ acc.opening_date }}</td>
              <td>
                <router-link :to="`/accounts/${acc.id}`" class="btn btn-ghost btn-sm">View</router-link>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!filtered.length" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--gray-300);margin:0 auto 12px;display:block"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          <p>No accounts found.</p>
        </div>
      </div>
    </div>

    <!-- Open Account Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Open New Account</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group" style="grid-column:1/-1">
                  <label class="form-label">Customer Name *</label>
                  <input v-model="form.customer" type="text" class="form-control" placeholder="Search customer name or CIF..." />
                </div>
                <div class="form-group">
                  <label class="form-label">Account Type *</label>
                  <select v-model="form.type" class="form-control">
                    <option value="">Select type</option>
                    <option v-for="t in types" :key="t" :value="t">{{ capitalize(t) }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Currency *</label>
                  <select v-model="form.currency" class="form-control">
                    <option>USD</option><option>EUR</option><option>GBP</option><option>INR</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Branch *</label>
                  <select v-model="form.branch" class="form-control">
                    <option>Main Branch</option><option>Brooklyn Branch</option><option>LA Branch</option><option>Chicago Regional</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Initial Deposit ($)</label>
                  <input v-model="form.initial_deposit" type="number" class="form-control" placeholder="0.00" min="0" />
                </div>
              </div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="saveAccount" :disabled="saving">
                <span v-if="saving" class="spinner-sm-dark"></span>
                {{ saving ? 'Opening...' : 'Open Account' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const showModal = ref(false)
const saving    = ref(false)
const formError = ref('')
const search = ref(''); const statusFilter = ref(''); const typeFilter = ref('')
const statuses = ['active','frozen','dormant','closed','blocked']
const types    = ['savings','current','salary','nre','nro','overdraft']

const form = reactive({ customer:'', type:'', currency:'USD', branch:'Main Branch', initial_deposit:'' })

const stats = [
  { label:'Total Accounts', value:'8,432',  bg:'linear-gradient(135deg,#dbeafe,#bfdbfe)', iconColor:'#2563eb', iconPath:'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z' },
  { label:'Active',         value:'7,891',  bg:'linear-gradient(135deg,#d1fae5,#a7f3d0)', iconColor:'#059669', iconPath:'M20 6L9 17l-5-5' },
  { label:'Dormant',        value:'312',    bg:'linear-gradient(135deg,#fef3c7,#fde68a)', iconColor:'#d97706', iconPath:'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
  { label:'Total Balance',  value:'$92.4M', bg:'linear-gradient(135deg,#ede9fe,#ddd6fe)', iconColor:'#7c3aed', iconPath:'M12 2v6m0 0l3-3m-3 3L9 5 M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8' },
]

const accounts = ref([
  { id:1, account_number:'1000-0042-5218', customer:'Alice Johnson',  type:'savings',  currency:'USD', available_balance:48250,  ledger_balance:48250,  status:'active', opening_date:'2020-03-15' },
  { id:2, account_number:'1000-0042-5219', customer:'Alice Johnson',  type:'current',  currency:'USD', available_balance:12800,  ledger_balance:12800,  status:'active', opening_date:'2020-04-01' },
  { id:3, account_number:'2000-0078-3341', customer:'Bob Williams',   type:'savings',  currency:'USD', available_balance:8500,   ledger_balance:8500,   status:'active', opening_date:'2021-07-22' },
  { id:4, account_number:'3000-0091-9910', customer:'David Martinez', type:'current',  currency:'USD', available_balance:275000, ledger_balance:275000, status:'active', opening_date:'2019-11-05' },
  { id:5, account_number:'4000-0055-1128', customer:'Carol Davis',    type:'salary',   currency:'USD', available_balance:3200,   ledger_balance:3200,   status:'frozen', opening_date:'2022-01-10' },
  { id:6, account_number:'5000-0033-7832', customer:'Eva Garcia',     type:'savings',  currency:'EUR', available_balance:95000,  ledger_balance:95000,  status:'active', opening_date:'2018-06-30' },
])

const filtered = computed(() => accounts.value.filter(a => {
  const q = search.value.toLowerCase()
  return (!q || `${a.account_number} ${a.customer}`.toLowerCase().includes(q))
      && (!statusFilter.value || a.status === statusFilter.value)
      && (!typeFilter.value   || a.type   === typeFilter.value)
}))

function closeModal() { showModal.value = false; formError.value = ''; Object.assign(form, { customer:'', type:'', currency:'USD', branch:'Main Branch', initial_deposit:'' }) }
function capitalize(s) { return s?.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase()) }
function statusBadge(s) {
  return { active:'badge-success', frozen:'badge-info', dormant:'badge-warning', closed:'badge-gray', blocked:'badge-danger' }[s] ?? 'badge-gray'
}

async function saveAccount() {
  formError.value = ''
  if (!form.customer.trim()) { formError.value = 'Customer name is required.'; return }
  if (!form.type)             { formError.value = 'Please select an account type.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 800)) // simulate API
  const newAcc = {
    id: Date.now(),
    account_number: '6000-' + String(Math.floor(Math.random()*9000)+1000) + '-' + String(Math.floor(Math.random()*9000)+1000),
    customer:           form.customer,
    type:               form.type,
    currency:           form.currency,
    available_balance:  parseFloat(form.initial_deposit) || 0,
    ledger_balance:     parseFloat(form.initial_deposit) || 0,
    status:             'active',
    opening_date:       new Date().toISOString().split('T')[0],
  }
  accounts.value.unshift(newAcc)
  saving.value = false
  closeModal()
  toast.success(`Account ${newAcc.account_number} opened successfully.`)
}
</script>

<style scoped>
.acc-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
.mono { font-family:monospace; font-size:.82rem; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.spinner-sm-dark { width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;margin-right:6px; }
@media (max-width:900px) { .acc-stats { grid-template-columns:repeat(2,1fr); } }
</style>
