<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">Loans & Credit</h1>
        <p class="page-subtitle">Manage loan applications and active loans</p>
      </div>
      <button class="btn btn-primary" @click="showModal = true">+ New Application</button>
    </div>

    <div class="loan-stats">
      <div class="stat-card" v-for="s in stats" :key="s.label">
        <div><p class="stat-label">{{ s.label }}</p><p class="stat-value">{{ s.value }}</p></div>
        <div class="stat-icon" :style="{ background: s.bg }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" :style="{ color: s.iconColor, width:'22px', height:'22px' }">
            <path :d="s.iconPath"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="tabs">
      <button v-for="tab in tabs" :key="tab.key" class="tab-btn" :class="{active: activeTab === tab.key}" @click="activeTab = tab.key">
        {{ tab.label }}
        <span class="tab-count">{{ tabCount(tab.key) }}</span>
      </button>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Loan ID</th><th>Customer</th><th>Type</th><th>Amount</th>
              <th>Outstanding</th><th>EMI</th><th>Rate</th><th>Status</th><th>Next EMI</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in filteredLoans" :key="l.id">
              <td class="mono text-sm">{{ l.loan_id }}</td>
              <td>{{ l.customer }}</td>
              <td><span class="badge badge-primary">{{ l.type }}</span></td>
              <td class="font-bold">${{ l.amount.toLocaleString() }}</td>
              <td>${{ l.outstanding.toLocaleString() }}</td>
              <td>${{ l.emi.toLocaleString() }}/mo</td>
              <td>{{ l.rate }}%</td>
              <td><span class="badge" :class="loanStatusBadge(l.status)">{{ l.status }}</span></td>
              <td class="text-muted text-sm">{{ l.next_emi }}</td>
              <td>
                <div class="flex gap-1">
                  <router-link :to="`/loans/${l.id}`" class="btn btn-ghost btn-sm">View</router-link>
                  <button v-if="l.status === 'applied'" class="btn btn-primary btn-sm" @click="approveLoan(l)">Approve</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!filteredLoans.length" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--gray-300);margin:0 auto 12px;display:block"><path d="M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8"/><path d="M12 2v6m0 0l3-3m-3 3L9 5"/></svg>
          <p>No loans found in this category.</p>
        </div>
      </div>
    </div>

    <!-- New Loan Application Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 style="font-weight:700">New Loan Application</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">Customer Name *</label>
                  <input v-model="form.customer" type="text" class="form-control" placeholder="Customer name or CIF" />
                </div>
                <div class="form-group">
                  <label class="form-label">Loan Type *</label>
                  <select v-model="form.type" class="form-control">
                    <option value="">Select loan type</option>
                    <option v-for="t in loanTypes" :key="t" :value="t">{{ t }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Principal Amount ($) *</label>
                  <input v-model="form.amount" type="number" class="form-control" placeholder="e.g. 50000" min="1000" />
                </div>
                <div class="form-group">
                  <label class="form-label">Interest Rate (% p.a.) *</label>
                  <input v-model="form.rate" type="number" class="form-control" placeholder="e.g. 9.5" min="0.01" step="0.1" />
                </div>
                <div class="form-group">
                  <label class="form-label">Tenure (months) *</label>
                  <input v-model="form.tenure" type="number" class="form-control" placeholder="e.g. 60" min="1" max="360" />
                </div>
                <div class="form-group">
                  <label class="form-label">Monthly EMI (calculated)</label>
                  <input type="text" class="form-control" :value="calculatedEmi" readonly style="background:var(--gray-50)" />
                </div>
                <div class="form-group" style="grid-column:1/-1">
                  <label class="form-label">Purpose</label>
                  <textarea v-model="form.purpose" class="form-control" rows="2" placeholder="Briefly describe the loan purpose..."></textarea>
                </div>
              </div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="submitApplication" :disabled="saving">
                {{ saving ? 'Submitting...' : 'Submit Application' }}
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
const activeTab = ref('all')

const loanTypes = ['Home Loan','Personal Loan','Auto Loan','Business Loan','Education Loan','Gold Loan','Agriculture Loan']

const form = reactive({ customer:'', type:'', amount:'', rate:'', tenure:'', purpose:'' })

const tabs = [
  { key:'all',     label:'All Loans' },
  { key:'active',  label:'Active' },
  { key:'applied', label:'Pending' },
  { key:'overdue', label:'Overdue' },
  { key:'closed',  label:'Closed' },
]

const stats = [
  { label:'Total Portfolio', value:'$21.7M', bg:'linear-gradient(135deg,#dbeafe,#bfdbfe)', iconColor:'#2563eb', iconPath:'M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8 M12 2v6m0 0l3-3m-3 3L9 5' },
  { label:'Active Loans',    value:'3',      bg:'linear-gradient(135deg,#d1fae5,#a7f3d0)', iconColor:'#059669', iconPath:'M20 6L9 17l-5-5' },
  { label:'Overdue Loans',   value:'1',      bg:'linear-gradient(135deg,#fef3c7,#fde68a)', iconColor:'#d97706', iconPath:'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z M12 9v4 M12 17h.01' },
  { label:'NPA Accounts',    value:'0',      bg:'linear-gradient(135deg,#fee2e2,#fca5a5)', iconColor:'#dc2626', iconPath:'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' },
]

const loans = ref([
  { id:1, loan_id:'LN-2024-001', customer:'Alice Johnson',  type:'Home Loan',     amount:500000, outstanding:420000, emi:3200, rate:7.5,  status:'active',  next_emi:'Jul 01, 2026' },
  { id:2, loan_id:'LN-2024-002', customer:'Bob Williams',   type:'Personal Loan', amount:50000,  outstanding:32000,  emi:1800, rate:12.5, status:'active',  next_emi:'Jul 05, 2026' },
  { id:3, loan_id:'LN-2024-003', customer:'Carol Davis',    type:'Business Loan', amount:200000, outstanding:200000, emi:4500, rate:9.0,  status:'applied', next_emi:'—' },
  { id:4, loan_id:'LN-2023-018', customer:'David Martinez', type:'Auto Loan',     amount:45000,  outstanding:28000,  emi:1200, rate:8.2,  status:'overdue', next_emi:'Jun 15, 2026' },
  { id:5, loan_id:'LN-2022-005', customer:'Eva Garcia',     type:'Education Loan',amount:80000,  outstanding:60000,  emi:2100, rate:6.5,  status:'active',  next_emi:'Jul 10, 2026' },
  { id:6, loan_id:'LN-2020-012', customer:'Frank Lee',      type:'Personal Loan', amount:30000,  outstanding:0,      emi:0,    rate:11.0, status:'closed',  next_emi:'—' },
])

const filteredLoans = computed(() =>
  activeTab.value === 'all' ? loans.value : loans.value.filter(l => l.status === activeTab.value)
)

function tabCount(key) {
  return key === 'all' ? loans.value.length : loans.value.filter(l => l.status === key).length
}

const calculatedEmi = computed(() => {
  const p = parseFloat(form.amount)
  const r = parseFloat(form.rate) / 12 / 100
  const n = parseInt(form.tenure)
  if (!p || !r || !n) return '—'
  const emi = p * r * Math.pow(1+r,n) / (Math.pow(1+r,n)-1)
  return '$' + emi.toFixed(2)
})

function loanStatusBadge(s) {
  return { active:'badge-success', applied:'badge-warning', overdue:'badge-danger', closed:'badge-gray', rejected:'badge-danger' }[s] ?? 'badge-gray'
}

function closeModal() {
  showModal.value = false
  formError.value = ''
  Object.assign(form, { customer:'', type:'', amount:'', rate:'', tenure:'', purpose:'' })
}

async function submitApplication() {
  formError.value = ''
  if (!form.customer.trim()) { formError.value = 'Customer name is required.'; return }
  if (!form.type)            { formError.value = 'Please select a loan type.'; return }
  if (!form.amount || parseFloat(form.amount) < 1000) { formError.value = 'Minimum loan amount is $1,000.'; return }
  if (!form.rate   || parseFloat(form.rate) <= 0)     { formError.value = 'Please enter a valid interest rate.'; return }
  if (!form.tenure || parseInt(form.tenure) < 1)      { formError.value = 'Please enter a valid tenure.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 900))

  const newLoan = {
    id:         Date.now(),
    loan_id:    'LN-' + new Date().getFullYear() + '-' + String(loans.value.length + 1).padStart(3,'0'),
    customer:   form.customer,
    type:       form.type,
    amount:     parseFloat(form.amount),
    outstanding:parseFloat(form.amount),
    emi:        parseFloat(calculatedEmi.value.replace('$','')),
    rate:       parseFloat(form.rate),
    status:     'applied',
    next_emi:   '—',
  }
  loans.value.unshift(newLoan)
  saving.value = false
  closeModal()
  toast.success(`Loan application ${newLoan.loan_id} submitted successfully.`)
}

function approveLoan(loan) {
  loan.status = 'active'
  loan.next_emi = new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'})
  toast.success(`Loan ${loan.loan_id} approved.`)
}
</script>

<style scoped>
.loan-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
.tabs { display:flex; gap:4px; margin-bottom:16px; background:var(--white); border:1px solid var(--gray-200); border-radius:var(--radius); padding:6px; flex-wrap:wrap; }
.tab-btn { display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:var(--radius-sm); font-size:.85rem; font-weight:600; border:none; background:none; color:var(--gray-500); transition:all .15s; cursor:pointer; }
.tab-btn.active  { background:var(--primary); color:white; }
.tab-btn:hover:not(.active) { background:var(--gray-100); color:var(--gray-700); }
.tab-count { background:var(--gray-200); color:var(--gray-600); font-size:.68rem; font-weight:700; padding:1px 6px; border-radius:10px; }
.tab-btn.active .tab-count { background:rgba(255,255,255,.25); color:white; }
.mono { font-family:monospace; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.modal-lg { max-width:640px; }
@media (max-width:900px) { .loan-stats { grid-template-columns:repeat(2,1fr); } }
</style>
