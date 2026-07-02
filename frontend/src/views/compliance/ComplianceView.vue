<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">AML & Compliance</h1><p class="page-subtitle">Anti-money laundering monitoring and compliance management</p></div>
      <button class="btn btn-primary" @click="showModal = true">+ Create Alert</button>
    </div>

    <div class="aml-stats">
      <div class="stat-card" v-for="s in stats" :key="s.label">
        <div><p class="stat-label">{{ s.label }}</p><p class="stat-value">{{ s.value }}</p></div>
        <div class="stat-icon" :style="{ background: s.bg }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" :style="{ color: s.iconColor, width:'22px', height:'22px' }">
            <path :d="s.iconPath"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">AML Alerts</span>
        <div class="flex gap-2">
          <select v-model="statusFilter" class="form-control" style="width:150px">
            <option value="">All Statuses</option>
            <option v-for="s in alertStatuses" :key="s" :value="s">{{ capitalize(s) }}</option>
          </select>
          <select v-model="severityFilter" class="form-control" style="width:140px">
            <option value="">All Severities</option>
            <option v-for="s in severities" :key="s" :value="s">{{ capitalize(s) }}</option>
          </select>
        </div>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Alert ID</th><th>Customer</th><th>Account</th><th>Amount</th><th>Rule Triggered</th><th>Severity</th><th>Status</th><th>Raised</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="a in filtered" :key="a.id">
              <td class="mono text-sm font-bold">{{ a.alert_id }}</td>
              <td>{{ a.customer }}</td>
              <td class="mono text-xs">{{ a.account }}</td>
              <td class="font-bold">${{ a.amount.toLocaleString() }}</td>
              <td class="text-sm">{{ a.rule }}</td>
              <td><span class="badge" :class="severityBadge(a.severity)">{{ a.severity }}</span></td>
              <td><span class="badge" :class="statusBadge(a.status)">{{ a.status }}</span></td>
              <td class="text-muted text-sm">{{ a.raised }}</td>
              <td>
                <div class="flex gap-1">
                  <button class="btn btn-ghost btn-sm" @click="openReview(a)">Review</button>
                  <button v-if="a.status === 'open'" class="btn btn-ghost btn-sm" @click="resolveAlert(a)">Resolve</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Alert Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Create AML Alert</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group"><label class="form-label">Customer Name *</label><input v-model="form.customer" type="text" class="form-control" /></div>
              <div class="form-group"><label class="form-label">Account Number</label><input v-model="form.account" type="text" class="form-control" placeholder="****XXXX" /></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">Severity *</label>
                  <select v-model="form.severity" class="form-control">
                    <option value="">Select severity</option>
                    <option v-for="s in severities" :key="s" :value="s">{{ capitalize(s) }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Amount Involved ($)</label>
                  <input v-model="form.amount" type="number" class="form-control" placeholder="0.00" />
                </div>
              </div>
              <div class="form-group"><label class="form-label">Rule / Reason *</label><input v-model="form.rule" type="text" class="form-control" placeholder="e.g. Threshold Breach > $10K" /></div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="createAlert" :disabled="saving">{{ saving ? 'Creating...' : 'Create Alert' }}</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Review Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="reviewModal" @click.self="reviewModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Review Alert — {{ selectedAlert?.alert_id }}</h3>
              <button class="btn btn-icon btn-ghost" @click="reviewModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body" v-if="selectedAlert">
              <div class="detail-rows" style="margin-bottom:16px">
                <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value">{{ selectedAlert.customer }}</span></div>
                <div class="detail-row"><span class="detail-label">Rule</span><span class="detail-value">{{ selectedAlert.rule }}</span></div>
                <div class="detail-row"><span class="detail-label">Amount</span><span class="detail-value font-bold">${{ selectedAlert.amount.toLocaleString() }}</span></div>
                <div class="detail-row"><span class="detail-label">Severity</span><span class="badge" :class="severityBadge(selectedAlert.severity)">{{ selectedAlert.severity }}</span></div>
              </div>
              <div class="form-group">
                <label class="form-label">Update Status</label>
                <select v-model="reviewForm.status" class="form-control">
                  <option value="open">Open</option>
                  <option value="under_review">Under Review</option>
                  <option value="resolved">Resolved</option>
                  <option value="false_positive">False Positive</option>
                  <option value="escalated">Escalated</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Resolution Notes</label>
                <textarea v-model="reviewForm.notes" class="form-control" rows="3" placeholder="Add review notes..."></textarea>
              </div>
              <div class="flex items-center gap-2" style="margin-top:8px">
                <input type="checkbox" id="str_filed" v-model="reviewForm.str_filed" />
                <label for="str_filed" style="font-size:.875rem;cursor:pointer">File Suspicious Transaction Report (STR)</label>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="reviewModal=false">Cancel</button>
              <button class="btn btn-primary" @click="saveReview">Save Review</button>
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
const showModal    = ref(false)
const reviewModal  = ref(false)
const saving       = ref(false)
const formError    = ref('')
const statusFilter   = ref('')
const severityFilter = ref('')
const selectedAlert  = ref(null)

const alertStatuses = ['open','under_review','resolved','false_positive','escalated']
const severities    = ['low','medium','high','critical']

const form       = reactive({ customer:'', account:'', severity:'', amount:'', rule:'' })
const reviewForm = reactive({ status:'under_review', notes:'', str_filed:false })

const stats = computed(() => [
  { label:'Open Alerts',         value: alerts.value.filter(a=>a.status==='open').length,         bg:'linear-gradient(135deg,#fee2e2,#fca5a5)', iconColor:'#dc2626', iconPath:'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z M12 9v4 M12 17h.01' },
  { label:'Under Review',        value: alerts.value.filter(a=>a.status==='under_review').length,  bg:'linear-gradient(135deg,#fef3c7,#fde68a)', iconColor:'#d97706', iconPath:'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
  { label:'Resolved This Month', value: alerts.value.filter(a=>a.status==='resolved').length,      bg:'linear-gradient(135deg,#d1fae5,#a7f3d0)', iconColor:'#059669', iconPath:'M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z M9 12l2 2 4-4' },
  { label:'STRs Filed',          value: alerts.value.filter(a=>a.str_filed).length,                bg:'linear-gradient(135deg,#dbeafe,#bfdbfe)', iconColor:'#2563eb', iconPath:'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z M14 2v6h6 M16 13H8 M16 17H8' },
])

const alerts = ref([
  { id:1, alert_id:'AML-001', customer:'Unknown',      account:'****9983', amount:32000, rule:'Threshold Breach > $10K', severity:'high',     status:'open',         raised:'Today 09:42', str_filed:false },
  { id:2, alert_id:'AML-002', customer:'Bob Williams', account:'****3341', amount:9800,  rule:'Velocity Check',          severity:'medium',   status:'under_review', raised:'Today 08:15', str_filed:false },
  { id:3, alert_id:'AML-003', customer:'Carol Davis',  account:'****1128', amount:15000, rule:'Unusual Pattern',         severity:'critical', status:'open',         raised:'Yesterday',   str_filed:false },
  { id:4, alert_id:'AML-004', customer:'Frank Lee',    account:'****7210', amount:4500,  rule:'Country Risk',            severity:'low',      status:'resolved',     raised:'2 days ago',  str_filed:false },
])

const filtered = computed(() => alerts.value.filter(a =>
  (!statusFilter.value   || a.status   === statusFilter.value) &&
  (!severityFilter.value || a.severity === severityFilter.value)
))

function capitalize(s) { return s?.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase()) }
function severityBadge(s) { return { low:'badge-info', medium:'badge-warning', high:'badge-danger', critical:'badge-danger' }[s] ?? 'badge-gray' }
function statusBadge(s)   { return { open:'badge-danger', under_review:'badge-warning', resolved:'badge-success', false_positive:'badge-gray', escalated:'badge-info' }[s] ?? 'badge-gray' }

function closeModal() {
  showModal.value = false; formError.value = ''
  Object.assign(form, { customer:'', account:'', severity:'', amount:'', rule:'' })
}

async function createAlert() {
  formError.value = ''
  if (!form.customer.trim()) { formError.value = 'Customer name is required.'; return }
  if (!form.severity)        { formError.value = 'Please select a severity.'; return }
  if (!form.rule.trim())     { formError.value = 'Rule/reason is required.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 600))

  const newAlert = {
    id:        Date.now(),
    alert_id:  'AML-' + String(alerts.value.length + 1).padStart(3,'0'),
    customer:  form.customer,
    account:   form.account || '****0000',
    amount:    parseFloat(form.amount) || 0,
    rule:      form.rule,
    severity:  form.severity,
    status:    'open',
    raised:    'Just now',
    str_filed: false,
  }
  alerts.value.unshift(newAlert)
  saving.value = false
  closeModal()
  toast.success(`Alert ${newAlert.alert_id} created.`)
}

function openReview(alert) {
  selectedAlert.value = alert
  reviewForm.status   = alert.status === 'open' ? 'under_review' : alert.status
  reviewForm.notes    = ''
  reviewForm.str_filed = alert.str_filed
  reviewModal.value   = true
}

function saveReview() {
  if (selectedAlert.value) {
    selectedAlert.value.status    = reviewForm.status
    selectedAlert.value.str_filed = reviewForm.str_filed
    toast.success(`Alert ${selectedAlert.value.alert_id} updated${reviewForm.str_filed ? ' — STR filed.' : '.'}`)
  }
  reviewModal.value = false
}

function resolveAlert(alert) {
  alert.status = 'resolved'
  toast.success(`Alert ${alert.alert_id} resolved.`)
}
</script>

<style scoped>
.aml-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
.mono { font-family:monospace; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.detail-rows { display:flex; flex-direction:column; gap:8px; }
.detail-row  { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--gray-100); }
.detail-row:last-child { border-bottom:none; }
.detail-label { font-size:.75rem; color:var(--gray-500); font-weight:600; text-transform:uppercase; }
.detail-value { font-size:.875rem; font-weight:600; color:var(--gray-800); }
@media (max-width:900px) { .aml-stats { grid-template-columns:repeat(2,1fr); } }
</style>
