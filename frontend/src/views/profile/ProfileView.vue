<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">My Profile</h1><p class="page-subtitle">Manage your account settings</p></div>
      <button class="btn btn-primary" @click="saveProfile" :disabled="profileSaving">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        {{ profileSaving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <div class="profile-grid">
      <!-- Avatar card -->
      <div class="card">
        <div class="card-body" style="text-align:center">
          <div class="profile-avatar-lg">{{ initials }}</div>
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:4px">{{ profileForm.first_name }} {{ profileForm.last_name }}</h3>
          <p class="text-muted text-sm">{{ roleLabel }}</p>
          <p class="text-muted text-xs" style="margin-top:4px">{{ profileForm.branch }} · {{ profileForm.location }}</p>
          <button class="btn btn-ghost btn-sm" style="margin-top:16px" @click="photoInput?.click()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Change Photo
          </button>
          <input ref="photoInput" type="file" accept="image/*" style="display:none" @change="onPhotoSelected" />
        </div>
      </div>

      <div>
        <!-- Personal Info -->
        <div class="card" style="margin-bottom:16px">
          <div class="card-header"><span class="card-title">Personal Information</span></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
              <div class="form-group">
                <label class="form-label">First Name</label>
                <input v-model="profileForm.first_name" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Last Name</label>
                <input v-model="profileForm.last_name" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input v-model="profileForm.email" type="email" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input v-model="profileForm.phone" type="tel" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Branch</label>
                <input v-model="profileForm.branch" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Location</label>
                <input v-model="profileForm.location" type="text" class="form-control" />
              </div>
            </div>
          </div>
        </div>

        <!-- Security -->
        <div class="card">
          <div class="card-header"><span class="card-title">Change Password</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Current Password</label>
              <input v-model="pwForm.current" type="password" class="form-control" placeholder="••••••••••" autocomplete="current-password" />
            </div>
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input v-model="pwForm.newPw" type="password" class="form-control" placeholder="Min. 10 characters" autocomplete="new-password" />
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <input v-model="pwForm.confirm" type="password" class="form-control" placeholder="••••••••••" autocomplete="new-password" />
            </div>
            <div v-if="pwError" class="form-err">{{ pwError }}</div>
            <div v-if="pwSuccess" class="form-success">Password changed successfully.</div>
            <button class="btn btn-primary btn-sm" style="margin-top:4px" @click="changePassword" :disabled="pwSaving">
              {{ pwSaving ? 'Updating...' : 'Update Password' }}
            </button>

            <div class="security-item">
              <div>
                <p style="font-size:.875rem;font-weight:700">Two-Factor Authentication</p>
                <p class="text-muted text-sm">{{ twoFaEnabled ? 'Enabled via SMS OTP' : 'Not enabled — recommended for staff accounts' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge" :class="twoFaEnabled ? 'badge-success' : 'badge-warning'">{{ twoFaEnabled ? 'Enabled' : 'Disabled' }}</span>
                <button class="btn btn-ghost btn-sm" @click="toggle2FA">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                  {{ twoFaEnabled ? 'Disable' : 'Enable' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth  = useAuthStore()
const toast = useToast()

const photoInput  = ref(null)
const profileSaving = ref(false)
const pwSaving    = ref(false)
const pwError     = ref('')
const pwSuccess   = ref(false)
const twoFaEnabled = ref(true)

// Populate from auth store; fall back to placeholders
const profileForm = reactive({
  first_name: auth.user?.customer?.first_name || auth.user?.staff?.first_name || 'Admin',
  last_name:  auth.user?.customer?.last_name  || auth.user?.staff?.last_name  || 'User',
  email:      auth.user?.email    || 'admin@bank.com',
  phone:      auth.user?.phone    || '+1-202-555-0001',
  branch:     'Main Branch',
  location:   'New York',
})

const pwForm = reactive({ current:'', newPw:'', confirm:'' })

const initials = computed(() => {
  const f = profileForm.first_name?.[0] || ''
  const l = profileForm.last_name?.[0]  || ''
  return (f + l).toUpperCase() || 'AU'
})

const roleLabel = computed(() => {
  const map = {
    super_admin:'Super Admin', bank_admin:'Bank Admin',
    branch_manager:'Branch Manager', teller:'Teller',
    customer:'Customer', loan_officer:'Loan Officer',
    credit_manager:'Credit Manager', compliance_officer:'Compliance Officer',
    auditor:'Auditor', it_admin:'IT Admin', accountant:'Accountant',
  }
  return map[auth.userRole] ?? auth.userRole ?? 'Staff'
})

async function saveProfile() {
  profileSaving.value = true
  await new Promise(r => setTimeout(r, 700))
  profileSaving.value = false
  toast.success('Profile updated successfully.')
}

async function changePassword() {
  pwError.value   = ''
  pwSuccess.value = false
  if (!pwForm.current)       { pwError.value = 'Current password is required.'; return }
  if (!pwForm.newPw)         { pwError.value = 'New password is required.'; return }
  if (pwForm.newPw.length < 10) { pwError.value = 'Password must be at least 10 characters.'; return }
  if (pwForm.newPw !== pwForm.confirm) { pwError.value = 'Passwords do not match.'; return }

  pwSaving.value = true
  await new Promise(r => setTimeout(r, 800))
  pwSaving.value = false
  pwSuccess.value = true
  Object.assign(pwForm, { current:'', newPw:'', confirm:'' })
  setTimeout(() => pwSuccess.value = false, 4000)
}

function toggle2FA() {
  twoFaEnabled.value = !twoFaEnabled.value
  toast.success(`Two-factor authentication ${twoFaEnabled.value ? 'enabled' : 'disabled'}.`)
}

function onPhotoSelected(e) {
  const file = e.target.files?.[0]
  if (file) toast.success(`Photo "${file.name}" uploaded successfully.`)
  if (e.target) e.target.value = ''
}
</script>

<style scoped>
.profile-grid { display:grid; grid-template-columns:260px 1fr; gap:16px; align-items:start; }
.profile-avatar-lg { width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;font-size:1.6rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 12px; }
.security-item { display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-top:1px solid var(--gray-100);margin-top:20px;gap:12px; }
.form-err     { background:#fee2e2; color:var(--danger);  border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-bottom:8px; }
.form-success { background:#d1fae5; color:#065f46;         border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-bottom:8px; }
@media (max-width:768px) { .profile-grid { grid-template-columns:1fr; } }
</style>
