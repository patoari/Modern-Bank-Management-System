<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Fixed & Recurring Deposits</h1><p class="page-subtitle">FD and RD management</p></div>
      <button class="btn btn-primary" @click="showModal = true">+ New Deposit</button>
    </div>

    <div class="tabs">
      <button v-for="tab in tabDefs" :key="tab.key" class="tab-btn" :class="{active: activeTab === tab.key}" @click="activeTab = tab.key">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
          <path :d="tab.iconPath"/>
        </svg>
        {{ tab.label }}
      </button>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Deposit ID</th><th>Customer</th><th>Principal</th><th>Rate</th>
              <th>Tenure</th><th>Maturity Date</th><th>Maturity Amount</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in activeList" :key="d.id">
              <td class="mono text-sm">{{ d.id }}</td>
              <td>{{ d.customer }}</td>
              <td class="font-bold">${{ d.principal.toLocaleString() }}</td>
              <td>{{ d.rate }}% p.a.</td>
              <td>{{ d.tenure }}</td>
              <td>{{ d.maturity }}</td>
              <td class="font-bold text-success">${{ d.maturity_amount.toLocaleString() }}</td>
              <td><span class="badge" :class="d.status === 'active' ? 'badge-success' : 'badge-gray'">{{ d.status }}</span></td>
              <td>
                <button class="btn btn-ghost btn-sm" @click="viewDeposit(d)">View</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!activeList.length" class="empty-state">
          <p>No deposits found.</p>
        </div>
      </div>
    </div>

    <!-- New Deposit Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">New {{ activeTab === 'fixed' ? 'Fixed' : 'Recurring' }} Deposit</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group" style="grid-column:1/-1">
                  <label class="form-label">Customer Name *</label>
                  <input v-model="form.customer" type="text" class="form-control" placeholder="Customer name" />
                </div>
                <div class="form-group">
                  <label class="form-label">Principal Amount ($) *</label>
                  <input v-model="form.principal" type="number" class="form-control" placeholder="Min. $1,000" min="1000" />
                </div>
                <div class="form-group">
                  <label class="form-label">Interest Rate (% p.a.) *</label>
                  <input v-model="form.rate" type="number" class="form-control" placeholder="e.g. 6.5" step="0.1" />
                </div>
                <div class="form-group">
                  <label class="form-label">Tenure *</label>
                  <select v-model="form.tenure" class="form-control">
                    <option value="">Select tenure</option>
                    <option value="6 Months">6 Months</option>
                    <option value="1 Year">1 Year</option>
                    <option value="18 Months">18 Months</option>
                    <option value="2 Years">2 Years</option>
                    <option value="3 Years">3 Years</option>
                    <option value="5 Years">5 Years</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Maturity Amount (calc.)</label>
                  <input type="text" class="form-control" :value="calcMaturity" readonly style="background:var(--gray-50)" />
                </div>
              </div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="createDeposit" :disabled="saving">
                {{ saving ? 'Creating...' : 'Create Deposit' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- View Deposit Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="viewModal" @click.self="viewModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Deposit — {{ selectedDeposit?.id }}</h3>
              <button class="btn btn-icon btn-ghost" @click="viewModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body" v-if="selectedDeposit">
              <div class="detail-rows">
                <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value">{{ selectedDeposit.customer }}</span></div>
                <div class="detail-row"><span class="detail-label">Principal</span><span class="detail-value">${{ selectedDeposit.principal.toLocaleString() }}</span></div>
                <div class="detail-row"><span class="detail-label">Rate</span><span class="detail-value">{{ selectedDeposit.rate }}% p.a.</span></div>
                <div class="detail-row"><span class="detail-label">Tenure</span><span class="detail-value">{{ selectedDeposit.tenure }}</span></div>
                <div class="detail-row"><span class="detail-label">Maturity Date</span><span class="detail-value">{{ selectedDeposit.maturity }}</span></div>
                <div class="detail-row"><span class="detail-label">Maturity Amount</span><span class="detail-value font-bold text-success">${{ selectedDeposit.maturity_amount.toLocaleString() }}</span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="badge" :class="selectedDeposit.status === 'active' ? 'badge-success':'badge-gray'">{{ selectedDeposit.status }}</span></div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-danger btn-sm" @click="closeDeposit">Close Deposit Early</button>
              <button class="btn btn-ghost" @click="viewModal=false">Close</button>
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
const activeTab      = ref('fixed')
const showModal      = ref(false)
const viewModal      = ref(false)
const saving         = ref(false)
const formError      = ref('')
const selectedDeposit = ref(null)

const tabDefs = [
  { key:'fixed',     label:'Fixed Deposits',    iconPath:'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z' },
  { key:'recurring', label:'Recurring Deposits', iconPath:'M1 4v6h6 M23 20v-6h-6 M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15' },
]

const form = reactive({ customer:'', principal:'', rate:'', tenure:'' })

const fds = ref([
  { id:'FD-001', customer:'Alice Johnson',  principal:100000, rate:6.5, tenure:'2 Years',   maturity:'Mar 15, 2026', maturity_amount:113806, status:'active' },
  { id:'FD-002', customer:'David Martinez', principal:500000, rate:7.0, tenure:'3 Years',   maturity:'Nov 05, 2026', maturity_amount:614907, status:'active' },
  { id:'FD-003', customer:'Eva Garcia',     principal:250000, rate:5.5, tenure:'1 Year',    maturity:'Jun 30, 2026', maturity_amount:263812, status:'active' },
  { id:'FD-004', customer:'Bob Williams',   principal:50000,  rate:6.0, tenure:'18 Months', maturity:'Jan 22, 2026', maturity_amount:54640,  status:'matured' },
])

const rds = ref([
  { id:'RD-001', customer:'Carol Davis', principal:1000, rate:5.5, tenure:'12 Months', maturity:'Jan 10, 2027', maturity_amount:12345, status:'active' },
  { id:'RD-002', customer:'Frank Lee',   principal:5000, rate:6.0, tenure:'24 Months', maturity:'Apr 18, 2026', maturity_amount:63500, status:'active' },
])

const activeList = computed(() => activeTab.value === 'fixed' ? fds.value : rds.value)

const tenureMonths = { '6 Months':6, '1 Year':12, '18 Months':18, '2 Years':24, '3 Years':36, '5 Years':60 }

const calcMaturity = computed(() => {
  const p = parseFloat(form.principal)
  const r = parseFloat(form.rate) / 100
  const months = tenureMonths[form.tenure]
  if (!p || !r || !months) return '—'
  const maturity = p * Math.pow(1 + r / 12, months)
  return '$' + maturity.toFixed(2)
})

function closeModal() {
  showModal.value = false; formError.value = ''
  Object.assign(form, { customer:'', principal:'', rate:'', tenure:'' })
}

async function createDeposit() {
  formError.value = ''
  if (!form.customer.trim())               { formError.value = 'Customer name is required.'; return }
  if (!form.principal || parseFloat(form.principal) < 1000) { formError.value = 'Minimum deposit is $1,000.'; return }
  if (!form.rate || parseFloat(form.rate) <= 0)             { formError.value = 'Please enter a valid interest rate.'; return }
  if (!form.tenure)                        { formError.value = 'Please select a tenure.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 800))

  const months   = tenureMonths[form.tenure]
  const p        = parseFloat(form.principal)
  const matDate  = new Date(Date.now() + months * 30 * 24 * 60*60*1000)
  const matLabel = matDate.toLocaleDateString('en-US',{ month:'short', day:'2-digit', year:'numeric' })
  const matAmt   = parseFloat(calcMaturity.value.replace('$',''))
  const prefix   = activeTab.value === 'fixed' ? 'FD' : 'RD'
  const id       = `${prefix}-${String(Date.now()).slice(-3)}`

  const entry = { id, customer:form.customer, principal:p, rate:parseFloat(form.rate), tenure:form.tenure, maturity:matLabel, maturity_amount:Math.round(matAmt), status:'active' }
  if (activeTab.value === 'fixed') fds.value.unshift(entry)
  else rds.value.unshift(entry)

  saving.value = false
  closeModal()
  toast.success(`${prefix} ${id} created successfully.`)
}

function viewDeposit(d) {
  selectedDeposit.value = d
  viewModal.value = true
}

function closeDeposit() {
  if (selectedDeposit.value) {
    selectedDeposit.value.status = 'closed'
    toast.success(`Deposit ${selectedDeposit.value.id} closed.`)
  }
  viewModal.value = false
}
</script>

<style scoped>
.tabs { display:flex; gap:4px; margin-bottom:16px; }
.tab-btn { display:flex; align-items:center; gap:7px; padding:9px 20px; border-radius:var(--radius-sm); font-size:.875rem; font-weight:600; border:1px solid var(--gray-200); background:var(--white); color:var(--gray-500); cursor:pointer; transition:all .15s; }
.tab-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
.mono { font-family:monospace; }
.text-success { color:var(--success); font-weight:600; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.detail-rows { display:flex; flex-direction:column; gap:8px; }
.detail-row  { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--gray-100); }
.detail-row:last-child { border-bottom:none; }
.detail-label { font-size:.75rem; color:var(--gray-500); font-weight:600; text-transform:uppercase; }
.detail-value { font-size:.875rem; font-weight:600; color:var(--gray-800); }
.btn-danger { background:var(--danger); color:white; border:none; }
.btn-danger:hover { background:#c53030; }
</style>
