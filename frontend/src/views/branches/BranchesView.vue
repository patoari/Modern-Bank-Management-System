<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Branches & ATMs</h1><p class="page-subtitle">Manage branch network and ATM locations</p></div>
      <button class="btn btn-primary" @click="showModal = true">+ Add Branch</button>
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
        <table class="table" v-if="activeTab === 'branches'">
          <thead><tr><th>Branch Code</th><th>Name</th><th>Type</th><th>City</th><th>IFSC</th><th>Manager</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="b in branches" :key="b.id">
              <td class="mono font-bold">{{ b.code }}</td>
              <td>{{ b.name }}</td>
              <td><span class="badge badge-primary">{{ b.type }}</span></td>
              <td>{{ b.city }}, {{ b.state }}</td>
              <td class="mono text-sm">{{ b.ifsc }}</td>
              <td>{{ b.manager }}</td>
              <td><span class="badge" :class="b.status === 'active' ? 'badge-success' : 'badge-warning'">{{ b.status }}</span></td>
              <td>
                <button class="btn btn-ghost btn-sm" @click="editBranch(b)">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
        <table class="table" v-else>
          <thead><tr><th>ATM ID</th><th>Name</th><th>Location</th><th>City</th><th>Cash Available</th><th>Last Refill</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="a in atms" :key="a.id">
              <td class="mono font-bold">{{ a.atm_id }}</td>
              <td>{{ a.name }}</td>
              <td>{{ a.location_type }}</td>
              <td>{{ a.city }}</td>
              <td :class="a.cash < 50000 ? 'text-warning font-bold' : 'font-bold'">${{ a.cash.toLocaleString() }}</td>
              <td class="text-muted text-sm">{{ a.last_refill }}</td>
              <td><span class="badge" :class="atmStatusBadge(a.status)">{{ a.status }}</span></td>
              <td>
                <button class="btn btn-ghost btn-sm" @click="replenishAtm(a)">Replenish</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Branch Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 style="font-weight:700">{{ editingBranch ? 'Edit Branch' : 'Add New Branch' }}</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">Branch Code *</label>
                  <input v-model="form.code" type="text" class="form-control" placeholder="BR-006" :disabled="!!editingBranch" />
                </div>
                <div class="form-group">
                  <label class="form-label">Branch Name *</label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="e.g. Downtown Branch" />
                </div>
                <div class="form-group">
                  <label class="form-label">Branch Type *</label>
                  <select v-model="form.type" class="form-control">
                    <option value="">Select type</option>
                    <option>Head Office</option><option>Branch</option><option>Regional</option><option>Sub Branch</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">IFSC Code *</label>
                  <input v-model="form.ifsc" type="text" class="form-control" placeholder="MBMS0000006" :disabled="!!editingBranch" />
                </div>
                <div class="form-group">
                  <label class="form-label">City *</label>
                  <input v-model="form.city" type="text" class="form-control" placeholder="New York" />
                </div>
                <div class="form-group">
                  <label class="form-label">State</label>
                  <input v-model="form.state" type="text" class="form-control" placeholder="NY" />
                </div>
                <div class="form-group">
                  <label class="form-label">Manager</label>
                  <input v-model="form.manager" type="text" class="form-control" placeholder="Manager name" />
                </div>
                <div class="form-group">
                  <label class="form-label">Status</label>
                  <select v-model="form.status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="saveBranch" :disabled="saving">
                {{ saving ? 'Saving...' : (editingBranch ? 'Save Changes' : 'Add Branch') }}
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

const toast = useToast()
const activeTab     = ref('branches')
const showModal     = ref(false)
const saving        = ref(false)
const formError     = ref('')
const editingBranch = ref(null)

const tabDefs = [
  { key:'branches', label:'Branches', iconPath:'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z' },
  { key:'atms',     label:'ATMs',     iconPath:'M3 9h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M3 9l2-5h14l2 5 M12 12v4 M10 14h4' },
]

const form = reactive({ code:'', name:'', type:'', ifsc:'', city:'', state:'', manager:'', status:'active' })

const branches = ref([
  { id:1, code:'BR-001', name:'Head Office — Main Branch', type:'Head Office', city:'New York',    state:'NY', ifsc:'MBMS0000001', manager:'John Adams',  status:'active' },
  { id:2, code:'BR-002', name:'Brooklyn Branch',           type:'Branch',      city:'Brooklyn',    state:'NY', ifsc:'MBMS0000002', manager:'Sarah Lee',   status:'active' },
  { id:3, code:'BR-003', name:'Los Angeles Branch',        type:'Branch',      city:'Los Angeles', state:'CA', ifsc:'MBMS0000003', manager:'Mike Chen',   status:'active' },
  { id:4, code:'BR-004', name:'Chicago Regional',          type:'Regional',    city:'Chicago',     state:'IL', ifsc:'MBMS0000004', manager:'Lisa Park',   status:'active' },
  { id:5, code:'BR-005', name:'Houston Sub-Branch',        type:'Sub Branch',  city:'Houston',     state:'TX', ifsc:'MBMS0000005', manager:'Tom Garcia',  status:'inactive' },
])

const atms = ref([
  { id:1, atm_id:'ATM-001', name:'Main Branch ATM',    location_type:'On-site',  city:'New York',    cash:250000, last_refill:'Today 08:00', status:'active' },
  { id:2, atm_id:'ATM-002', name:'Times Square ATM',   location_type:'Off-site', city:'New York',    cash:85000,  last_refill:'Yesterday',   status:'active' },
  { id:3, atm_id:'ATM-003', name:'Brooklyn Mall ATM',  location_type:'Off-site', city:'Brooklyn',    cash:42000,  last_refill:'2 days ago',  status:'active' },
  { id:4, atm_id:'ATM-004', name:'LA Branch ATM',      location_type:'On-site',  city:'Los Angeles', cash:180000, last_refill:'Today 06:00', status:'active' },
  { id:5, atm_id:'ATM-005', name:"O'Hare Airport ATM", location_type:'Off-site', city:'Chicago',     cash:15000,  last_refill:'3 days ago',  status:'under_maintenance' },
])

function atmStatusBadge(s) {
  return { active:'badge-success', under_maintenance:'badge-warning', out_of_service:'badge-danger', inactive:'badge-gray' }[s] ?? 'badge-gray'
}

function closeModal() {
  showModal.value = false; formError.value = ''; editingBranch.value = null
  Object.assign(form, { code:'', name:'', type:'', ifsc:'', city:'', state:'', manager:'', status:'active' })
}

function editBranch(b) {
  editingBranch.value = b
  Object.assign(form, { code:b.code, name:b.name, type:b.type, ifsc:b.ifsc, city:b.city, state:b.state, manager:b.manager, status:b.status })
  showModal.value = true
}

async function saveBranch() {
  formError.value = ''
  if (!form.code.trim()) { formError.value = 'Branch code is required.'; return }
  if (!form.name.trim()) { formError.value = 'Branch name is required.'; return }
  if (!form.type)        { formError.value = 'Please select a branch type.'; return }
  if (!form.city.trim()) { formError.value = 'City is required.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 700))

  if (editingBranch.value) {
    Object.assign(editingBranch.value, { name:form.name, type:form.type, city:form.city, state:form.state, manager:form.manager, status:form.status })
    toast.success(`Branch ${form.code} updated.`)
  } else {
    branches.value.push({ id:Date.now(), code:form.code, name:form.name, type:form.type, city:form.city, state:form.state, ifsc:form.ifsc || ('MBMS' + String(Date.now()).slice(-7)), manager:form.manager, status:form.status })
    toast.success(`Branch ${form.code} added.`)
  }

  saving.value = false
  closeModal()
}

function replenishAtm(atm) {
  atm.cash = 250000
  atm.last_refill = 'Just now'
  atm.status = 'active'
  toast.success(`ATM ${atm.atm_id} replenished with $250,000.`)
}
</script>

<style scoped>
.tabs { display:flex; gap:8px; margin-bottom:16px; }
.tab-btn { display:flex; align-items:center; gap:7px; padding:9px 20px; border-radius:var(--radius-sm); font-size:.875rem; font-weight:600; border:1px solid var(--gray-200); background:var(--white); color:var(--gray-500); cursor:pointer; transition:all .15s; }
.tab-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
.mono { font-family:monospace; }
.text-warning { color:var(--warning); }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
.modal-lg { max-width:640px; }
</style>
