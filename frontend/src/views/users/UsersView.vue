<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Users & Roles</h1><p class="page-subtitle">Manage system users, roles, and permissions</p></div>
      <button class="btn btn-primary" @click="openAddModal">+ Add User</button>
    </div>

    <!-- Role Cards -->
    <div class="role-cards">
      <div class="role-card" v-for="r in roleSummary" :key="r.role">
        <div class="role-icon-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
            <path :d="r.iconPath"/>
          </svg>
        </div>
        <div>
          <p class="role-name">{{ r.role }}</p>
          <p class="role-count">{{ r.count }} users</p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">All Users</span>
        <input type="text" v-model="search" class="form-control" placeholder="Search users..." style="width:220px" />
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>User</th><th>Role</th><th>Branch</th><th>Last Login</th><th>2FA</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="u in filtered" :key="u.id">
              <td>
                <div class="flex items-center gap-2">
                  <div class="user-av">{{ u.name[0] }}</div>
                  <div>
                    <p class="font-bold text-sm">{{ u.name }}</p>
                    <p class="text-muted text-xs">{{ u.email }}</p>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-primary">{{ u.role }}</span></td>
              <td class="text-muted text-sm">{{ u.branch }}</td>
              <td class="text-muted text-sm">{{ u.last_login }}</td>
              <td>
                <span class="twofa-badge" :class="u.two_fa ? 'twofa-on' : 'twofa-off'">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px">
                    <path v-if="u.two_fa" d="M20 6L9 17l-5-5"/>
                    <template v-else><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></template>
                  </svg>
                  {{ u.two_fa ? 'On' : 'Off' }}
                </span>
              </td>
              <td><span class="badge" :class="u.status === 'active' ? 'badge-success' : 'badge-warning'">{{ u.status }}</span></td>
              <td>
                <div class="flex gap-1">
                  <button class="btn btn-ghost btn-sm" @click="openEditModal(u)" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="btn btn-ghost btn-sm" @click="resetPassword(u)" title="Reset Password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add User Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="showModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">{{ editingUser ? 'Edit User' : 'Add New User' }}</h3>
              <button class="btn btn-icon btn-ghost" @click="showModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group"><label class="form-label">Full Name *</label><input v-model="form.name" type="text" class="form-control" /></div>
              <div class="form-group"><label class="form-label">Email *</label><input v-model="form.email" type="email" class="form-control" /></div>
              <div class="form-group"><label class="form-label">Role *</label>
                <select v-model="form.role" class="form-control">
                  <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Branch</label>
                <select v-model="form.branch" class="form-control">
                  <option>Main Branch</option><option>Brooklyn Branch</option><option>LA Branch</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showModal=false">Cancel</button>
              <button class="btn btn-primary" @click="saveUser">{{ editingUser ? 'Save Changes' : 'Create User' }}</button>
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
const search = ref('')
const editingUser = ref(null)
const form = reactive({ name:'', email:'', role:'Teller', branch:'Main Branch' })
const roles = ['Super Admin','Bank Admin','Branch Manager','Teller','Customer Service Officer','Loan Officer','Credit Manager','Accountant','Compliance Officer','Auditor','IT Administrator']

const roleSummary = [
  { role:'Super Admin',    count:1,  iconPath:'M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z' },
  { role:'Bank Admin',     count:2,  iconPath:'M3 9.5L12 4l9 5.5V20H3V9.5z M9 14h6v6H9z' },
  { role:'Branch Manager', count:4,  iconPath:'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M9 7a4 4 0 100 8 4 4 0 000-8z M23 21v-2a4 4 0 00-3-3.87' },
  { role:'Teller',         count:12, iconPath:'M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6' },
  { role:'Loan Officer',   count:6,  iconPath:'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z M14 2v6h6 M16 13H8 M16 17H8' },
  { role:'Compliance',     count:2,  iconPath:'M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z M9 12l2 2 4-4' },
]

const users = ref([
  { id:1, name:'System Administrator', email:'admin@bank.com',    role:'Super Admin',       branch:'Head Office', last_login:'Today 09:00',  two_fa:true,  status:'active' },
  { id:2, name:'John Adams',           email:'jadams@bank.com',   role:'Branch Manager',    branch:'Main Branch', last_login:'Today 08:45',  two_fa:true,  status:'active' },
  { id:3, name:'Sarah Lee',            email:'slee@bank.com',     role:'Branch Manager',    branch:'Brooklyn',    last_login:'Yesterday',    two_fa:true,  status:'active' },
  { id:4, name:'Mary Johnson',         email:'mjohnson@bank.com', role:'Teller',            branch:'Main Branch', last_login:'Today 08:00',  two_fa:false, status:'active' },
  { id:5, name:'James Brown',          email:'jbrown@bank.com',   role:'Loan Officer',      branch:'Main Branch', last_login:'2 days ago',   two_fa:false, status:'active' },
  { id:6, name:'Linda Davis',          email:'ldavis@bank.com',   role:'Compliance Officer',branch:'Head Office', last_login:'Today 10:15',  two_fa:true,  status:'active' },
  { id:7, name:'Robert Wilson',        email:'rwilson@bank.com',  role:'Auditor',           branch:'Head Office', last_login:'3 days ago',   two_fa:true,  status:'inactive' },
])
const filtered = computed(() => users.value.filter(u => !search.value || `${u.name} ${u.email} ${u.role}`.toLowerCase().includes(search.value.toLowerCase())))

function openAddModal() {
  editingUser.value = null
  Object.assign(form, { name:'', email:'', role:'Teller', branch:'Main Branch' })
  showModal.value = true
}

function openEditModal(u) {
  editingUser.value = u
  Object.assign(form, { name:u.name, email:u.email, role:u.role, branch:u.branch })
  showModal.value = true
}

function saveUser() {
  if (!form.name.trim() || !form.email.trim()) {
    toast.error('Name and email are required.'); return
  }
  if (editingUser.value) {
    Object.assign(editingUser.value, { name:form.name, email:form.email, role:form.role, branch:form.branch })
    toast.success('User updated successfully.')
  } else {
    users.value.push({
      id:         Date.now(),
      name:       form.name,
      email:      form.email,
      role:       form.role,
      branch:     form.branch,
      last_login: 'Never',
      two_fa:     false,
      status:     'active',
    })
    toast.success(`User ${form.name} created successfully.`)
  }
  showModal.value = false
}

function resetPassword(u) {
  toast.success(`Password reset link sent to ${u.email}.`)
}
</script>

<style scoped>
.role-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:12px; margin-bottom:16px; }
.role-card { display:flex; align-items:center; gap:10px; background:var(--white); border:1px solid var(--gray-200); border-radius:var(--radius); padding:14px; }
.role-icon-wrap { width:36px; height:36px; border-radius:var(--radius-sm); background:rgba(13,115,119,.08); color:var(--secondary); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.role-name  { font-size:.82rem; font-weight:700; color:var(--gray-800); }
.role-count { font-size:.75rem; color:var(--gray-500); }
.user-av { width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;font-size:.8rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.twofa-badge { display:inline-flex; align-items:center; gap:3px; padding:2px 7px; border-radius:4px; font-size:.72rem; font-weight:700; }
.twofa-on  { background:#d1fae5; color:#065f46; }
.twofa-off { background:#fee2e2; color:#991b1b; }
</style>
