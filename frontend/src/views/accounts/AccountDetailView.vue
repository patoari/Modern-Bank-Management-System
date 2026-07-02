<template>
  <div>
    <div class="page-header">
      <div class="flex items-center gap-3">
        <router-link to="/accounts" class="btn btn-ghost btn-sm">← Back</router-link>
        <div>
          <h1 class="page-title">Account: {{ account.account_number }}</h1>
          <p class="page-subtitle">{{ account.customer }} · {{ account.type }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <span class="badge" :class="account.status === 'active' ? 'badge-success' : 'badge-warning'">{{ account.status }}</span>
        <button class="btn btn-ghost btn-sm" @click="handleFreeze">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93L4.93 19.07"/></svg>
          {{ account.status === 'frozen' ? 'Unfreeze' : 'Freeze' }}
        </button>
        <button class="btn btn-primary btn-sm" @click="showTxnModal = true">+ New Transaction</button>
      </div>
    </div>

    <div class="detail-grid">
      <div class="card">
        <div class="card-header"><span class="card-title">Account Details</span></div>
        <div class="card-body">
          <div class="balance-display">
            <p class="bal-label">Available Balance</p>
            <p class="bal-value">${{ account.available_balance.toLocaleString() }}</p>
            <p class="bal-sub">Ledger: ${{ account.ledger_balance.toLocaleString() }} · Hold: $0.00</p>
          </div>
          <div class="detail-rows">
            <div class="detail-row" v-for="r in rows" :key="r.label">
              <span class="detail-label">{{ r.label }}</span>
              <span class="detail-value">{{ r.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">Transaction History</span>
          <button class="btn btn-ghost btn-sm" @click="downloadStatement">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Statement
          </button>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Ref</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Date</th></tr></thead>
          <tbody>
              <tr v-for="t in txns" :key="t.ref">
                <td class="mono text-xs">{{ t.ref }}</td>
                <td>{{ t.desc }}</td>
                <td class="text-danger">{{ t.debit ? '$' + t.debit.toLocaleString() : '—' }}</td>
                <td class="text-success">{{ t.credit ? '$' + t.credit.toLocaleString() : '—' }}</td>
                <td class="font-bold">${{ t.balance.toLocaleString() }}</td>
                <td class="text-muted text-sm">{{ t.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- New Transaction Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showTxnModal" @click.self="showTxnModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">New Transaction</h3>
              <button class="btn btn-icon btn-ghost" @click="showTxnModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Transaction Type *</label>
                <select v-model="txnForm.type" class="form-control">
                  <option value="deposit">Cash Deposit</option>
                  <option value="withdrawal">Cash Withdrawal</option>
                  <option value="transfer">Fund Transfer</option>
                </select>
              </div>
              <div class="form-group" v-if="txnForm.type === 'transfer'">
                <label class="form-label">To Account Number *</label>
                <input v-model="txnForm.to_account" type="text" class="form-control" placeholder="Account number" />
              </div>
              <div class="form-group">
                <label class="form-label">Amount *</label>
                <input v-model="txnForm.amount" type="number" class="form-control" placeholder="0.00" min="0.01" />
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <input v-model="txnForm.description" type="text" class="form-control" placeholder="Optional note" />
              </div>
              <div v-if="txnError" class="form-err">{{ txnError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showTxnModal=false">Cancel</button>
              <button class="btn btn-primary" @click="submitTransaction" :disabled="txnSaving">
                {{ txnSaving ? 'Processing...' : 'Submit' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { useRoute } from 'vue-router'

const route = useRoute()
const toast = useToast()
const showTxnModal = ref(false)
const txnSaving    = ref(false)
const txnError     = ref('')

const account = reactive({
  account_number: '1000-0042-5218',
  customer: 'Alice Johnson',
  type: 'Savings Account',
  status: 'active',
  available_balance: 48250,
  ledger_balance: 48250,
})

const txnForm = reactive({ type:'deposit', to_account:'', amount:'', description:'' })

const rows = [
  { label:'Account Type',  value:'Savings Account' },
  { label:'Currency',      value:'USD' },
  { label:'Branch',        value:'Main Branch — New York' },
  { label:'Interest Rate', value:'3.5% p.a.' },
  { label:'IBAN',          value:'US00 1000 0042 5218' },
  { label:'Opened',        value:'2020-03-15' },
  { label:'Last Activity', value:'Today, 10:24 AM' },
  { label:'Statement',     value:'Monthly via Email' },
]

const txns = ref([
  { ref:'TXN-001', desc:'Cash Deposit',        debit:null, credit:15000, balance:48250, date:'Today 10:24' },
  { ref:'TXN-002', desc:'Fund Transfer Out',   debit:8500, credit:null,  balance:33250, date:'Yesterday' },
  { ref:'TXN-003', desc:'Bill Payment — ELEC', debit:1200, credit:null,  balance:41750, date:'2 days ago' },
  { ref:'TXN-004', desc:'Salary Credit',       debit:null, credit:12000, balance:42950, date:'3 days ago' },
  { ref:'TXN-005', desc:'ATM Withdrawal',      debit:5000, credit:null,  balance:30950, date:'4 days ago' },
])

function handleFreeze() {
  if (account.status === 'active') {
    account.status = 'frozen'
    toast.success('Account frozen successfully.')
  } else {
    account.status = 'active'
    toast.success('Account unfrozen successfully.')
  }
}

function downloadStatement() {
  toast.success('Statement download started — check your downloads folder.')
}

async function submitTransaction() {
  txnError.value = ''
  if (!txnForm.amount || parseFloat(txnForm.amount) <= 0) {
    txnError.value = 'Please enter a valid amount.'; return
  }
  if (txnForm.type === 'transfer' && !txnForm.to_account.trim()) {
    txnError.value = 'Please enter the destination account number.'; return
  }
  const amt = parseFloat(txnForm.amount)
  if ((txnForm.type === 'withdrawal' || txnForm.type === 'transfer') && amt > account.available_balance) {
    txnError.value = 'Insufficient balance.'; return
  }

  txnSaving.value = true
  await new Promise(r => setTimeout(r, 800))

  const newRef = 'TXN-' + String(Date.now()).slice(-6)
  if (txnForm.type === 'deposit') {
    account.available_balance += amt
    account.ledger_balance    += amt
    txns.value.unshift({ ref: newRef, desc: txnForm.description || 'Cash Deposit', debit:null, credit:amt, balance:account.available_balance, date:'Just now' })
  } else {
    account.available_balance -= amt
    account.ledger_balance    -= amt
    txns.value.unshift({ ref: newRef, desc: txnForm.description || (txnForm.type === 'transfer' ? `Transfer to ${txnForm.to_account}` : 'Cash Withdrawal'), debit:amt, credit:null, balance:account.available_balance, date:'Just now' })
  }

  txnSaving.value = false
  showTxnModal.value = false
  Object.assign(txnForm, { type:'deposit', to_account:'', amount:'', description:'' })
  toast.success('Transaction processed successfully.')
}
</script>

<style scoped>
.detail-grid { display:grid; grid-template-columns:360px 1fr; gap:16px; }
.balance-display { background:linear-gradient(135deg,var(--primary-dark),var(--secondary)); border-radius:var(--radius); padding:24px; text-align:center; margin-bottom:20px; color:white; }
.bal-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.1em; opacity:.7; margin-bottom:6px; }
.bal-value { font-size:2rem; font-weight:800; margin-bottom:6px; }
.bal-sub   { font-size:.78rem; opacity:.6; }
.detail-rows { display:flex; flex-direction:column; gap:8px; }
.detail-row  { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--gray-100); }
.detail-row:last-child { border-bottom:none; }
.detail-label { font-size:.75rem; color:var(--gray-500); font-weight:600; text-transform:uppercase; }
.detail-value { font-size:.875rem; color:var(--gray-800); font-weight:600; }
.mono { font-family:monospace; }
.text-success { color:var(--success); font-weight:600; }
.text-danger  { color:var(--danger);  font-weight:600; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
@media (max-width:900px) { .detail-grid { grid-template-columns:1fr; } }
</style>
