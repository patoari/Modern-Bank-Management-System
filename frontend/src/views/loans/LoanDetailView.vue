<template>
  <div>
    <div class="page-header">
      <div class="flex items-center gap-3">
        <router-link to="/loans" class="btn btn-ghost btn-sm">← Back</router-link>
        <div>
          <h1 class="page-title">Loan: {{ loan.loan_id }}</h1>
          <p class="page-subtitle">{{ loan.customer }} · {{ loan.type }}</p>
        </div>
      </div>
      <span class="badge badge-success">{{ loan.status }}</span>
    </div>

    <div class="detail-grid">
      <div class="card">
        <div class="card-header"><span class="card-title">Loan Summary</span></div>
        <div class="card-body">
          <div class="loan-progress">
            <div class="lp-row">
              <span>Loan Amount</span><span class="font-bold">${{ loan.amount.toLocaleString() }}</span>
            </div>
            <div class="lp-row">
              <span>Outstanding</span><span class="font-bold text-danger">${{ loan.outstanding.toLocaleString() }}</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: repaidPct + '%' }"></div>
            </div>
            <p class="text-muted text-xs" style="text-align:right">{{ repaidPct }}% repaid</p>
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
        <div class="card-header"><span class="card-title">EMI Schedule (Recent)</span></div>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>Due Date</th><th>EMI</th><th>Principal</th><th>Interest</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="e in emiSchedule" :key="e.no">
                <td>{{ e.no }}</td>
                <td>{{ e.due }}</td>
                <td>${{ e.emi }}</td>
                <td>${{ e.principal }}</td>
                <td>${{ e.interest }}</td>
                <td><span class="badge" :class="e.paid ? 'badge-success' : 'badge-warning'">{{ e.paid ? 'Paid' : 'Upcoming' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const loan = { loan_id:'LN-2024-001', customer:'Alice Johnson', type:'Home Loan', amount:500000, outstanding:420000, status:'active', rate:7.5, tenure:240, emi:3200, disbursed:'2024-01-15', next_emi:'Jul 01, 2026' }
const repaidPct = computed(() => Math.round(((loan.amount - loan.outstanding) / loan.amount) * 100))
const rows = [
  { label:'Interest Rate',   value:`${loan.rate}% p.a. (Floating)` },
  { label:'Tenure',          value:`${loan.tenure} months` },
  { label:'Monthly EMI',     value:`$${loan.emi.toLocaleString()}` },
  { label:'Disbursed On',    value: loan.disbursed },
  { label:'Next EMI Due',    value: loan.next_emi },
  { label:'Collateral',      value:'Property — 123 Main St' },
]
const emiSchedule = [
  { no:1,  due:'Feb 01, 2024', emi:'3,200', principal:'1,075', interest:'2,125', paid:true },
  { no:2,  due:'Mar 01, 2024', emi:'3,200', principal:'1,082', interest:'2,118', paid:true },
  { no:3,  due:'Apr 01, 2024', emi:'3,200', principal:'1,089', interest:'2,111', paid:true },
  { no:4,  due:'May 01, 2024', emi:'3,200', principal:'1,096', interest:'2,104', paid:true },
  { no:29, due:'Jun 01, 2026', emi:'3,200', principal:'1,190', interest:'2,010', paid:true },
  { no:30, due:'Jul 01, 2026', emi:'3,200', principal:'1,197', interest:'2,003', paid:false },
  { no:31, due:'Aug 01, 2026', emi:'3,200', principal:'1,205', interest:'1,995', paid:false },
]
</script>

<style scoped>
.detail-grid { display: grid; grid-template-columns: 380px 1fr; gap: 16px; }
.detail-rows { display: flex; flex-direction: column; gap: 8px; }
.detail-row  { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--gray-100); }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-size: .75rem; color: var(--gray-500); font-weight: 600; text-transform: uppercase; }
.detail-value { font-size: .875rem; color: var(--gray-800); font-weight: 600; }
.loan-progress { margin-bottom: 20px; padding: 16px; background: var(--gray-50); border-radius: var(--radius); }
.lp-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: .875rem; }
.progress-bar { height: 8px; background: var(--gray-200); border-radius: 4px; margin: 8px 0 4px; }
.progress-fill { height: 100%; background: linear-gradient(to right, var(--secondary), var(--primary)); border-radius: 4px; transition: width .5s ease; }
.text-danger { color: var(--danger); }
@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
</style>
