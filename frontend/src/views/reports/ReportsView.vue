<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Reports & Analytics</h1><p class="page-subtitle">Generate and download banking reports</p></div>
      <button class="btn btn-primary" @click="showGenModal = true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Generate Report
      </button>
    </div>

    <div class="reports-grid">
      <div class="report-card" v-for="r in reports" :key="r.title" @click="generate(r)">
        <div class="report-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px">
            <path :d="r.iconPath"/>
          </svg>
        </div>
        <div>
          <p class="report-title">{{ r.title }}</p>
          <p class="report-desc">{{ r.desc }}</p>
        </div>
        <button class="btn btn-ghost btn-sm" style="margin-left:auto;flex-shrink:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </button>
      </div>
    </div>

    <div class="charts-row">
      <div class="card">
        <div class="card-header"><span class="card-title">Monthly Revenue</span></div>
        <div class="card-body">
          <apexchart type="bar" height="280" :options="barOpts" :series="barSeries" />
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">Loan Portfolio Mix</span></div>
        <div class="card-body">
          <apexchart type="pie" height="280" :options="pieOpts" :series="pieSeries" />
        </div>
      </div>
    </div>

    <!-- Generate Report Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showGenModal" @click.self="showGenModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Generate Report</h3>
              <button class="btn btn-icon btn-ghost" @click="showGenModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Report Type *</label>
                <select v-model="genForm.type" class="form-control">
                  <option value="">Select report type</option>
                  <option v-for="r in reports" :key="r.title" :value="r.title">{{ r.title }}</option>
                </select>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">From Date *</label>
                  <input v-model="genForm.from" type="date" class="form-control" />
                </div>
                <div class="form-group">
                  <label class="form-label">To Date *</label>
                  <input v-model="genForm.to" type="date" class="form-control" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Format</label>
                <div class="flex gap-2">
                  <label v-for="fmt in ['PDF','Excel','CSV']" :key="fmt" class="fmt-option" :class="{selected: genForm.format === fmt}" @click="genForm.format = fmt">
                    {{ fmt }}
                  </label>
                </div>
              </div>
              <div v-if="genError" class="form-err">{{ genError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showGenModal=false">Cancel</button>
              <button class="btn btn-primary" @click="downloadReport" :disabled="genSaving">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                {{ genSaving ? 'Generating...' : 'Download' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import VueApexCharts from 'vue3-apexcharts'
import { useToast } from 'vue-toastification'
import { ref, reactive } from 'vue'
const toast = useToast()
const apexchart = VueApexCharts

const showGenModal = ref(false)
const genSaving    = ref(false)
const genError     = ref('')
const genForm = reactive({ type:'', from:'', to:'', format:'PDF' })

const reports = [
  { iconPath:'M18 20V10M12 20V4M6 20v-6',                                                    title:'Daily Transaction Summary',   desc:'All transactions for the selected date' },
  { iconPath:'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M9 7a4 4 0 100 8 4 4 0 000-8z',      title:'Customer Acquisition Report', desc:'New customers by branch and segment' },
  { iconPath:'M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8 M12 2v6m0 0l3-3m-3 3L9 5',     title:'Loan Portfolio Report',       desc:'Active loans, NPAs, and repayment status' },
  { iconPath:'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z',                                     title:'Account Balance Summary',     desc:'Balances by account type and branch' },
  { iconPath:'M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z M9 12l2 2 4-4', title:'AML Compliance Report', desc:'Flagged transactions and alerts' },
  { iconPath:'M2 5h20v14H2z M2 10h20 M6 15h2 M6 13h0',                                       title:'Card Transaction Report',     desc:'Debit and credit card activity' },
  { iconPath:'M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6',                        title:'Interest Income Report',      desc:'Interest earned across deposits and loans' },
  { iconPath:'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8', title:'Audit Trail Report', desc:'User activity and system changes' },
]

const barOpts = {
  chart: { toolbar:{ show:false }, fontFamily:'Segoe UI, sans-serif' },
  colors: ['#0d7377','#f0a500'],
  xaxis: { categories:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], labels:{ style:{ colors:'#9ca3af', fontSize:'11px' } } },
  yaxis: { labels:{ formatter: v => `$${(v/1000).toFixed(0)}K`, style:{ colors:'#9ca3af' } } },
  dataLabels: { enabled:false }, grid: { borderColor:'#f3f4f6' }, legend: { position:'top' }
}
const barSeries = [
  { name:'Interest Income', data:[42,38,51,47,54,60,58,62,59,67,64,72].map(v=>v*1000) },
  { name:'Fee Income',      data:[12,11,15,13,16,18,17,19,18,21,19,22].map(v=>v*1000) },
]
const pieOpts = {
  labels:['Home Loan','Personal Loan','Business Loan','Auto Loan','Education','Other'],
  colors:['#1a3a5c','#0d7377','#f0a500','#10b981','#3b82f6','#9ca3af'],
  dataLabels:{ enabled:true, formatter: v => v.toFixed(1)+'%' },
  legend:{ position:'bottom', fontSize:'12px' }
}
const pieSeries = [38,24,18,12,5,3]

function generate(r) {
  genForm.type = r.title
  showGenModal.value = true
}

async function downloadReport() {
  genError.value = ''
  if (!genForm.type)  { genError.value = 'Please select a report type.'; return }
  if (!genForm.from)  { genError.value = 'From date is required.'; return }
  if (!genForm.to)    { genError.value = 'To date is required.'; return }
  if (genForm.from > genForm.to) { genError.value = 'From date cannot be after To date.'; return }

  genSaving.value = true
  await new Promise(r => setTimeout(r, 1000))

  // Simulate CSV download
  const csv  = `Report Type,${genForm.type}\nFrom,${genForm.from}\nTo,${genForm.to}\nFormat,${genForm.format}\nGenerated,${new Date().toISOString()}\n`
  const blob = new Blob([csv], { type:'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `${genForm.type.replace(/ /g,'_')}_${genForm.from}_${genForm.to}.${genForm.format.toLowerCase()}`
  a.click()
  URL.revokeObjectURL(url)

  genSaving.value    = false
  showGenModal.value = false
  Object.assign(genForm, { type:'', from:'', to:'', format:'PDF' })
  toast.success('Report downloaded successfully.')
}
</script>

<style scoped>
.reports-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; margin-bottom:24px; }
.report-card { display:flex; align-items:center; gap:14px; background:var(--white); border:1px solid var(--gray-200); border-radius:var(--radius); padding:16px; cursor:pointer; transition:all .15s; }
.report-card:hover { border-color:var(--secondary); box-shadow:var(--shadow); }
.report-icon { width:40px; height:40px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--gray-50); border-radius:var(--radius-sm); color:var(--primary); }
.report-title { font-size:.875rem; font-weight:700; color:var(--gray-800); }
.report-desc  { font-size:.75rem; color:var(--gray-500); margin-top:2px; }
.charts-row   { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.fmt-option { display:inline-flex; align-items:center; padding:6px 14px; border:1px solid var(--gray-200); border-radius:var(--radius-sm); font-size:.82rem; font-weight:600; cursor:pointer; transition:all .15s; }
.fmt-option.selected { background:var(--primary); color:white; border-color:var(--primary); }
.fmt-option:hover:not(.selected) { border-color:var(--secondary); color:var(--secondary); }
@media (max-width:900px) { .charts-row { grid-template-columns:1fr; } }
</style>
