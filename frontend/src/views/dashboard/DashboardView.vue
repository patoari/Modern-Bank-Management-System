<template>
  <div class="dashboard">
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, {{ auth.userName || 'User' }} — {{ today }}</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-ghost btn-sm" @click="exportDashboard">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <router-link to="/transactions" class="btn btn-primary btn-sm">+ New Transaction</router-link>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.label">
        <div>
          <p class="stat-label">{{ stat.label }}</p>
          <p class="stat-value">{{ stat.value }}</p>
          <p class="stat-change" :class="stat.trend">{{ stat.change }}</p>
        </div>
        <div class="stat-icon" :style="{ background: stat.bg }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" :style="{ color: stat.iconColor, width:'22px', height:'22px' }">
            <path :d="stat.iconPath"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <div class="card chart-card">
        <div class="card-header">
          <span class="card-title">Transaction Volume</span>
          <div class="flex gap-2">
            <button
              v-for="p in ['7D','1M','3M']"
              :key="p"
              class="btn btn-ghost btn-sm"
              :class="{ 'btn-primary': chartPeriod === p }"
              @click="chartPeriod = p"
            >{{ p }}</button>
          </div>
        </div>
        <div class="card-body">
          <apexchart type="area" height="260" :options="txnChartOptions" :series="txnSeries" />
        </div>
      </div>

      <div class="card chart-card chart-card--sm">
        <div class="card-header">
          <span class="card-title">Account Types</span>
        </div>
        <div class="card-body">
          <apexchart type="donut" height="260" :options="donutOptions" :series="donutSeries" />
        </div>
      </div>
    </div>

    <!-- Recent Transactions & Quick Actions -->
    <div class="bottom-row">
      <!-- Recent Transactions -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Transactions</span>
          <router-link to="/transactions" class="btn btn-ghost btn-sm">View All →</router-link>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Account</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="txn in recentTxns" :key="txn.ref">
                <td><span class="mono">{{ txn.ref }}</span></td>
                <td>{{ txn.type }}</td>
                <td><span class="mono">{{ txn.account }}</span></td>
                <td :class="txn.amount > 0 ? 'text-success' : 'text-danger'">
                  {{ txn.amount > 0 ? '+' : '' }}{{ formatCurrency(txn.amount) }}
                </td>
                <td><span class="badge" :class="statusBadge(txn.status)">{{ txn.status }}</span></td>
                <td class="text-muted">{{ txn.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Actions + Alerts -->
      <div class="side-panel">
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom:16px">
          <div class="card-header"><span class="card-title">Quick Actions</span></div>
          <div class="card-body quick-actions">
            <router-link v-for="qa in quickActions" :key="qa.label" :to="qa.to" class="qa-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="qa-icon">
                <path :d="qa.svgPath"/>
              </svg>
              <span class="qa-label">{{ qa.label }}</span>
            </router-link>
          </div>
        </div>

        <!-- Alerts -->
        <div class="card">
          <div class="card-header"><span class="card-title">Alerts</span></div>
          <div class="card-body" style="padding:0">
            <div v-for="alert in alerts" :key="alert.msg" class="alert-item">
              <span class="alert-dot" :class="'alert-' + alert.type"></span>
              <div>
                <p class="alert-msg">{{ alert.msg }}</p>
                <p class="alert-time text-muted text-xs">{{ alert.time }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const apexchart = VueApexCharts
const chartPeriod = ref('1M')

function exportDashboard() {
  toast.success('Dashboard summary exported')
}

const toast = useToast()

const today = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })

const stats = [
  { label: 'Total Deposits',    value: '$48.2M',  change: '↑ 12.4% this month', trend: 'up',   bg: 'linear-gradient(135deg,#dbeafe,#bfdbfe)', iconPath: 'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z', iconColor: '#2563eb' },
  { label: 'Active Loans',      value: '$21.7M',  change: '↑ 8.1% this month',  trend: 'up',   bg: 'linear-gradient(135deg,#d1fae5,#a7f3d0)', iconPath: 'M12 2v6m0 0l3-3m-3 3L9 5 M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8', iconColor: '#059669' },
  { label: 'Total Customers',   value: '14,382',  change: '↑ 3.2% this month',  trend: 'up',   bg: 'linear-gradient(135deg,#fef3c7,#fde68a)', iconPath: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M9 7a4 4 0 100 8 4 4 0 000-8z M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75', iconColor: '#d97706' },
  { label: 'Pending Approvals', value: '27',      change: '↓ 5 from yesterday', trend: 'down', bg: 'linear-gradient(135deg,#fee2e2,#fca5a5)', iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', iconColor: '#dc2626' },
]

const txnChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, type: 'area', fontFamily: 'Segoe UI, sans-serif' },
  colors: ['#0d7377', '#f0a500'],
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .3, opacityTo: 0 } },
  dataLabels: { enabled: false },
  xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], labels: { style: { colors: '#9ca3af', fontSize: '11px' } } },
  yaxis: { labels: { formatter: v => `$${(v/1000).toFixed(0)}K`, style: { colors: '#9ca3af', fontSize: '11px' } } },
  grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
  tooltip: { y: { formatter: v => `$${v.toLocaleString()}` } },
  legend: { position: 'top' }
}))

const txnSeries = [
  { name: 'Deposits',    data: [420000,380000,510000,470000,540000,600000,580000,620000,590000,670000,640000,720000] },
  { name: 'Withdrawals', data: [320000,290000,380000,350000,410000,450000,430000,480000,460000,510000,490000,540000] }
]

const donutOptions = {
  labels: ['Savings','Current','Loan','FD/RD','Other'],
  colors: ['#1a3a5c','#0d7377','#f0a500','#10b981','#9ca3af'],
  legend: { position: 'bottom', fontSize: '12px' },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '65%' } } }
}
const donutSeries = [42, 28, 15, 10, 5]

const recentTxns = [
  { ref: 'TXN-2024-001', type: 'Cash Deposit',    account: '****4521', amount:  15000, status: 'completed', date: 'Today, 10:24' },
  { ref: 'TXN-2024-002', type: 'Fund Transfer',   account: '****7832', amount: -8500,  status: 'completed', date: 'Today, 09:15' },
  { ref: 'TXN-2024-003', type: 'Loan EMI',        account: '****3341', amount: -2300,  status: 'processing',date: 'Today, 08:30' },
  { ref: 'TXN-2024-004', type: 'Cash Withdrawal', account: '****9910', amount: -5000,  status: 'completed', date: 'Yesterday' },
  { ref: 'TXN-2024-005', type: 'NEFT Transfer',   account: '****1128', amount:  32000, status: 'pending',   date: 'Yesterday' },
]

const quickActions = [
  { svgPath: 'M12 5v14M5 12h14', label: 'New Account',   to: '/accounts' },
  { svgPath: 'M7 16V4m0 0L3 8m4-4l4 4 M17 8v12m0 0l4-4m-4 4l-4-4', label: 'Fund Transfer', to: '/transactions' },
  { svgPath: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2 M12 11a4 4 0 100-8 4 4 0 000 8z M19 8l2 2 4-4', label: 'Add Customer',  to: '/customers' },
  { svgPath: 'M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8 M12 2v6m0 0l3-3m-3 3L9 5', label: 'Apply Loan',    to: '/loans' },
  { svgPath: 'M2 5h20v14H2z M2 10h20 M6 15h2', label: 'Issue Card',    to: '/cards' },
  { svgPath: 'M12 2a7 7 0 017 7c0 4-7 13-7 13S5 13 5 9a7 7 0 017-7z M12 9a2 2 0 100 4 2 2 0 000-4z', label: 'Open FD',      to: '/deposits' },
]

const alerts = [
  { type: 'danger',  msg: 'AML flag raised on account ****9983', time: '5 minutes ago' },
  { type: 'warning', msg: '3 loan EMIs overdue this week',        time: '1 hour ago' },
  { type: 'info',    msg: 'System maintenance scheduled 2:00 AM', time: '2 hours ago' },
  { type: 'success', msg: 'EOD processing completed successfully', time: '8 hours ago' },
]

function formatCurrency(v) {
  return `$${Math.abs(v).toLocaleString()}`
}

function statusBadge(s) {
  return { completed:'badge-success', pending:'badge-warning', processing:'badge-info', failed:'badge-danger' }[s] ?? 'badge-gray'
}
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.charts-row {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  margin-bottom: 24px;
}
.chart-card .card-body { padding: 16px; }

.bottom-row {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 16px;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.qa-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border-radius: var(--radius-sm);
  background: var(--gray-50);
  transition: all .15s;
  text-align: center;
}
.qa-item:hover { background: rgba(13,115,119,.08); color: var(--secondary); }
.qa-icon  { width: 20px; height: 20px; color: var(--gray-500); }
.qa-label { font-size: .72rem; font-weight: 600; color: var(--gray-600); }

.alert-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--gray-50);
}
.alert-item:last-child { border-bottom: none; }
.alert-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  margin-top: 5px;
  flex-shrink: 0;
}
.alert-danger  { background: var(--danger); }
.alert-warning { background: var(--warning); }
.alert-info    { background: var(--info); }
.alert-success { background: var(--success); }
.alert-msg  { font-size: .82rem; color: var(--gray-700); }
.alert-time { margin-top: 2px; }

.mono { font-family: monospace; font-size: .8rem; }
.text-success { color: var(--success); font-weight: 600; }
.text-danger  { color: var(--danger);  font-weight: 600; }

@media (max-width: 1200px) {
  .stats-grid  { grid-template-columns: repeat(2, 1fr); }
  .charts-row  { grid-template-columns: 1fr; }
  .bottom-row  { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
