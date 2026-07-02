<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">Transactions</h1>
        <p class="page-subtitle">View and manage all transactions</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-ghost btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <button class="btn btn-primary" @click="showModal = true">+ New Transaction</button>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px">
      <div class="card-body" style="padding:14px">
        <div class="flex gap-2 flex-wrap">
          <input v-model="filters.search" type="text" class="form-control" placeholder="Search reference, account..." style="flex:1;min-width:200px" />
          <select v-model="filters.type" class="form-control" style="width:180px">
            <option value="">All Types</option>
            <option v-for="t in txnTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <select v-model="filters.status" class="form-control" style="width:150px">
            <option value="">All Statuses</option>
            <option v-for="s in statuses" :key="s" :value="s">{{ capitalize(s) }}</option>
          </select>
          <input v-model="filters.from" type="date" class="form-control" style="width:150px" />
          <input v-model="filters.to"   type="date" class="form-control" style="width:150px" />
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">{{ filtered.length }} Transactions</span>
        <span class="text-muted text-sm">Total: ${{ totalAmount.toLocaleString() }}</span>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Reference</th><th>Type</th><th>Mode</th><th>From</th><th>To</th>
              <th>Amount</th><th>Fee</th><th>Status</th><th>Date</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in paginated" :key="t.id">
              <td><span class="mono text-sm">{{ t.ref }}</span></td>
              <td><span class="badge badge-primary">{{ t.type }}</span></td>
              <td class="text-muted text-sm">{{ capitalize(t.mode) }}</td>
              <td class="mono text-xs">{{ t.from || '—' }}</td>
              <td class="mono text-xs">{{ t.to   || '—' }}</td>
              <td class="font-bold">${{ t.amount.toLocaleString() }}</td>
              <td class="text-muted text-sm">${{ t.fee }}</td>
              <td><span class="badge" :class="statusBadge(t.status)">{{ t.status }}</span></td>
              <td class="text-muted text-sm">{{ t.date }}</td>
              <td>
                <button class="btn btn-ghost btn-sm" @click="viewTxn(t)" title="View details">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!filtered.length" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--gray-300);margin:0 auto 12px;display:block"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
          <p>No transactions found.</p>
        </div>
      </div>
      <div class="pagination">
        <span class="text-muted text-sm" style="flex:1">Showing {{ (page-1)*10+1 }}–{{ Math.min(page*10, filtered.length) }} of {{ filtered.length }}</span>
        <button class="page-btn" :disabled="page<=1" @click="page--">‹</button>
        <button v-for="p in totalPages" :key="p" class="page-btn" :class="{active:p===page}" @click="page=p">{{ p }}</button>
        <button class="page-btn" :disabled="page>=totalPages" @click="page++">›</button>
      </div>
    </div>

    <!-- Transaction Detail Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="selectedTxn" @click.self="selectedTxn=null">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Transaction Details</h3>
              <button class="btn btn-icon btn-ghost" @click="selectedTxn=null">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="txn-amount-display">
                <p>${{ selectedTxn.amount.toLocaleString() }}</p>
                <span class="badge" :class="statusBadge(selectedTxn.status)">{{ selectedTxn.status }}</span>
              </div>
              <div class="detail-rows">
                <div class="detail-row"><span>Reference</span><span class="mono">{{ selectedTxn.ref }}</span></div>
                <div class="detail-row"><span>Type</span><span>{{ selectedTxn.type }}</span></div>
                <div class="detail-row"><span>Mode</span><span>{{ capitalize(selectedTxn.mode) }}</span></div>
                <div class="detail-row"><span>From Account</span><span class="mono">{{ selectedTxn.from || '—' }}</span></div>
                <div class="detail-row"><span>To Account</span><span class="mono">{{ selectedTxn.to || '—' }}</span></div>
                <div class="detail-row"><span>Fee</span><span>${{ selectedTxn.fee }}</span></div>
                <div class="detail-row"><span>Date</span><span>{{ selectedTxn.date }}</span></div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost btn-sm" @click="printReceipt(selectedTxn)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Receipt
              </button>
              <button class="btn btn-ghost" @click="selectedTxn=null">Close</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- New Transaction Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="showModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">New Transaction</h3>
              <button class="btn btn-icon btn-ghost" @click="showModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Transaction Type *</label>
                <select v-model="newTxn.type" class="form-control">
                  <option v-for="t in txnTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">From Account</label>
                <input v-model="newTxn.from" type="text" class="form-control" placeholder="Account number" />
              </div>
              <div class="form-group">
                <label class="form-label">To Account</label>
                <input v-model="newTxn.to" type="text" class="form-control" placeholder="Account number" />
              </div>
              <div class="form-group">
                <label class="form-label">Amount *</label>
                <input v-model="newTxn.amount" type="number" class="form-control" placeholder="0.00" min="0" />
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea v-model="newTxn.description" class="form-control" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showModal=false">Cancel</button>
              <button class="btn btn-primary" @click="submitTxn">Submit Transaction</button>
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
const showModal   = ref(false)
const selectedTxn = ref(null)
const page        = ref(1)

const filters = reactive({ search:'', type:'', status:'', from:'', to:'' })
const newTxn  = reactive({ type:'cash_deposit', from:'', to:'', amount:'', description:'' })

const statuses = ['pending','processing','completed','failed','reversed','cancelled']
const txnTypes = [
  { value:'cash_deposit',    label:'Cash Deposit' },
  { value:'cash_withdrawal', label:'Cash Withdrawal' },
  { value:'fund_transfer',   label:'Fund Transfer' },
  { value:'neft',            label:'NEFT' },
  { value:'rtgs',            label:'RTGS' },
  { value:'upi',             label:'UPI' },
  { value:'bill_payment',    label:'Bill Payment' },
  { value:'loan_emi',        label:'Loan EMI' },
]

const transactions = ref([
  { id:1, ref:'TXN-2026-001', type:'Cash Deposit',    mode:'branch',           from:null,             to:'1000-0042-5218', amount:15000, fee:0,   status:'completed',  date:'Today 10:24' },
  { id:2, ref:'TXN-2026-002', type:'Fund Transfer',   mode:'internet_banking', from:'1000-0042-5218', to:'2000-0078-3341', amount:8500,  fee:25,  status:'completed',  date:'Today 09:15' },
  { id:3, ref:'TXN-2026-003', type:'Loan EMI',        mode:'mobile_banking',   from:'3000-0091-9910', to:'LOAN-ACC-001',   amount:2300,  fee:0,   status:'processing', date:'Today 08:30' },
  { id:4, ref:'TXN-2026-004', type:'Cash Withdrawal', mode:'atm',              from:'4000-0055-1128', to:null,             amount:5000,  fee:2.5, status:'completed',  date:'Yesterday' },
  { id:5, ref:'TXN-2026-005', type:'NEFT',            mode:'internet_banking', from:'5000-0033-7832', to:'EXT-9983214578', amount:32000, fee:15,  status:'pending',    date:'Yesterday' },
  { id:6, ref:'TXN-2026-006', type:'Bill Payment',    mode:'mobile_banking',   from:'1000-0042-5218', to:'ELEC-BOARD',     amount:1200,  fee:0,   status:'completed',  date:'2 days ago' },
])

const filtered    = computed(() => transactions.value.filter(t => {
  const q = filters.search.toLowerCase()
  return (!q || `${t.ref} ${t.from||''} ${t.to||''}`.toLowerCase().includes(q))
      && (!filters.type   || t.type.toLowerCase().replace(/ /g,'_') === filters.type)
      && (!filters.status || t.status === filters.status)
}))
const totalPages  = computed(() => Math.max(1, Math.ceil(filtered.value.length/10)))
const paginated   = computed(() => filtered.value.slice((page.value-1)*10, page.value*10))
const totalAmount = computed(() => filtered.value.reduce((s,t) => s + t.amount, 0))

function capitalize(s) { return s?.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase()) }
function statusBadge(s) {
  return { completed:'badge-success', pending:'badge-warning', processing:'badge-info', failed:'badge-danger', reversed:'badge-gray', cancelled:'badge-gray' }[s] ?? 'badge-gray'
}
function viewTxn(t) { selectedTxn.value = t }

function printReceipt(txn) {
  if (!txn) return
  const lines = [
    '============================',
    '   TRANSACTION RECEIPT',
    '============================',
    `Ref:    ${txn.ref}`,
    `Type:   ${txn.type}`,
    `Mode:   ${capitalize(txn.mode)}`,
    `From:   ${txn.from || '—'}`,
    `To:     ${txn.to   || '—'}`,
    `Amount: $${txn.amount.toLocaleString()}`,
    `Fee:    $${txn.fee}`,
    `Status: ${txn.status.toUpperCase()}`,
    `Date:   ${txn.date}`,
    '============================',
  ].join('\n')
  const w = window.open('', '_blank', 'width=400,height=500')
  w.document.write(`<pre style="font-family:monospace;padding:20px">${lines}</pre>`)
  w.document.close()
  w.print()
}
function submitTxn() {
  if (!newTxn.amount || newTxn.amount <= 0) { toast.error('Please enter a valid amount'); return }
  toast.success('Transaction submitted for processing')
  showModal.value = false
}
</script>

<style scoped>
.mono { font-family: monospace; }
.txn-amount-display { text-align:center; padding:20px; background:var(--gray-50); border-radius:var(--radius); margin-bottom:20px; }
.txn-amount-display p { font-size:2rem; font-weight:800; color:var(--gray-900); margin-bottom:8px; }
.detail-rows { display:flex; flex-direction:column; gap:8px; }
.detail-row  { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--gray-100); font-size:.875rem; }
.detail-row:last-child { border-bottom:none; }
.detail-row span:first-child { color:var(--gray-500); font-weight:600; font-size:.78rem; text-transform:uppercase; }
</style>
