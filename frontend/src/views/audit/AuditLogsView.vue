<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Audit Logs</h1><p class="page-subtitle">System activity and security events</p></div>
      <button class="btn btn-ghost btn-sm" @click="exportLogs">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export
      </button>
    </div>

    <div class="card" style="margin-bottom:16px">
      <div class="card-body" style="padding:14px">
        <div class="flex gap-2 flex-wrap">
          <input v-model="search" type="text" class="form-control" placeholder="Search user, action, module..." style="flex:1" />
          <select v-model="moduleFilter" class="form-control" style="width:160px">
            <option value="">All Modules</option>
            <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
          </select>
          <select v-model="actionFilter" class="form-control" style="width:140px">
            <option value="">All Actions</option>
            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Timestamp</th><th>User</th><th>Role</th><th>Module</th><th>Action</th><th>Details</th><th>IP Address</th><th>Result</th></tr></thead>
          <tbody>
            <tr v-for="log in filtered" :key="log.id">
              <td class="text-muted text-sm mono">{{ log.ts }}</td>
              <td class="text-sm font-bold">{{ log.user }}</td>
              <td><span class="badge badge-primary text-xs">{{ log.role }}</span></td>
              <td class="text-sm">{{ log.module }}</td>
              <td><span class="action-tag" :class="'action-' + log.type">{{ log.action }}</span></td>
              <td class="text-muted text-sm">{{ log.details }}</td>
              <td class="mono text-xs">{{ log.ip }}</td>
              <td>
                <span class="result-badge" :class="log.success ? 'result-success' : 'result-fail'">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px">
                    <path v-if="log.success" d="M20 6L9 17l-5-5"/>
                    <template v-else><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></template>
                  </svg>
                  {{ log.success ? 'Success' : 'Failed' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
const toast = useToast()
const search = ref(''); const moduleFilter = ref(''); const actionFilter = ref('')
const modules = ['Authentication','Accounts','Transactions','Loans','Users','Settings','Reports']
const actions  = ['Login','Logout','Create','Update','Delete','Approve','Export','View']
const logs = ref([
  { id:1, ts:'2026-06-24 10:24:01', user:'John Adams',   role:'Branch Manager',     module:'Transactions',  action:'Approve', type:'write', details:'Approved TXN-2026-005 ($32,000)',      ip:'192.168.1.10', success:true },
  { id:2, ts:'2026-06-24 10:18:33', user:'Mary Johnson', role:'Teller',             module:'Transactions',  action:'Create',  type:'write', details:'Cash deposit $15,000 to ****5218',    ip:'192.168.1.25', success:true },
  { id:3, ts:'2026-06-24 09:55:12', user:'Linda Davis',  role:'Compliance Officer', module:'Reports',       action:'Export',  type:'read',  details:'Exported AML report Q2 2026',          ip:'192.168.1.40', success:true },
  { id:4, ts:'2026-06-24 09:44:08', user:'Unknown',      role:'—',                  module:'Authentication',action:'Login',   type:'auth',  details:'Failed login attempt — admin@bank.com', ip:'85.24.133.7',  success:false },
  { id:5, ts:'2026-06-24 09:30:00', user:'System Admin', role:'Super Admin',        module:'Users',         action:'Create',  type:'write', details:'Created user: rwilson@bank.com',        ip:'192.168.1.5',  success:true },
  { id:6, ts:'2026-06-24 08:55:44', user:'James Brown',  role:'Loan Officer',       module:'Loans',         action:'Update',  type:'write', details:'Updated loan LN-2024-003 status',       ip:'192.168.1.32', success:true },
])
const filtered = computed(() => logs.value.filter(l => {
  const q = search.value.toLowerCase()
  return (!q || `${l.user} ${l.action} ${l.module} ${l.details}`.toLowerCase().includes(q))
      && (!moduleFilter.value || l.module === moduleFilter.value)
      && (!actionFilter.value || l.action === actionFilter.value)
}))

function exportLogs() {
  const rows = [
    ['Timestamp','User','Role','Module','Action','Details','IP','Result'],
    ...filtered.value.map(l => [l.ts, l.user, l.role, l.module, l.action, `"${l.details}"`, l.ip, l.success ? 'Success' : 'Failed'])
  ]
  const csv  = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type:'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `audit-logs-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.success('Audit logs exported as CSV.')
}
</script>

<style scoped>
.mono { font-family:monospace; }
.action-tag   { display:inline-flex; padding:2px 8px; border-radius:4px; font-size:.72rem; font-weight:700; text-transform:uppercase; }
.action-auth  { background:#ede9fe; color:#5b21b6; }
.action-write { background:#dbeafe; color:#1e40af; }
.action-read  { background:#d1fae5; color:#065f46; }
.result-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:4px; font-size:.72rem; font-weight:700; }
.result-success { background:#d1fae5; color:#065f46; }
.result-fail    { background:#fee2e2; color:#991b1b; }
</style>
