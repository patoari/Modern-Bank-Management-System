<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">System Settings</h1><p class="page-subtitle">Configure system-wide parameters</p></div>
      <button class="btn btn-primary" @click="saveSettings" :disabled="saving">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <div v-if="savedMsg" class="save-banner">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0"><path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z M9 12l2 2 4-4"/></svg>
      Settings saved successfully.
    </div>

    <div class="settings-grid">
      <div class="settings-nav card">
        <div v-for="tab in tabs" :key="tab.key" class="settings-nav-item" :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0">
            <path :d="tab.iconPath"/>
          </svg>
          <span>{{ tab.label }}</span>
        </div>
      </div>

      <div class="card settings-panel">
        <!-- General -->
        <template v-if="activeTab === 'general'">
          <div class="card-header"><span class="card-title">General Settings</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Bank Name</label>
              <input v-model="general.bank_name" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label class="form-label">Short Name / Acronym</label>
              <input v-model="general.short_name" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label class="form-label">Default Currency</label>
              <select v-model="general.currency" class="form-control">
                <option value="USD">USD — US Dollar</option>
                <option value="EUR">EUR — Euro</option>
                <option value="GBP">GBP — British Pound</option>
                <option value="INR">INR — Indian Rupee</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Timezone</label>
              <select v-model="general.timezone" class="form-control">
                <option value="America/New_York">America/New_York (EST)</option>
                <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                <option value="America/Chicago">America/Chicago (CST)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Date Format</label>
              <select v-model="general.date_format" class="form-control">
                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                <option value="YYYY-MM-DD">YYYY-MM-DD (ISO)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Contact Email</label>
              <input v-model="general.contact_email" type="email" class="form-control" />
            </div>
          </div>
        </template>

        <!-- Security -->
        <template v-if="activeTab === 'security'">
          <div class="card-header"><span class="card-title">Security Settings</span></div>
          <div class="card-body">
            <div class="setting-toggle" v-for="s in securityToggles" :key="s.key">
              <div>
                <p class="toggle-label">{{ s.label }}</p>
                <p class="toggle-desc text-muted text-sm">{{ s.desc }}</p>
              </div>
              <div class="toggle-wrap">
                <input type="checkbox" :id="s.key" v-model="s.value" class="toggle-input" />
                <label :for="s.key" class="toggle-label-el"></label>
              </div>
            </div>
            <div class="setting-divider"></div>
            <div class="form-group">
              <label class="form-label">Max Failed Login Attempts</label>
              <input v-model.number="security.max_attempts" type="number" class="form-control" min="3" max="10" style="width:120px" />
              <p class="form-hint">After this many failed attempts, the account will be locked for 30 minutes.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Session Timeout (minutes)</label>
              <input v-model.number="security.session_timeout" type="number" class="form-control" min="5" max="120" style="width:120px" />
            </div>
            <div class="form-group">
              <label class="form-label">High-Value Transaction Threshold ($)</label>
              <input v-model.number="security.hv_threshold" type="number" class="form-control" min="1000" style="width:160px" />
              <p class="form-hint">Transactions above this amount require manager approval.</p>
            </div>
          </div>
        </template>

        <!-- Notifications -->
        <template v-if="activeTab === 'notifications'">
          <div class="card-header"><span class="card-title">Notification Settings</span></div>
          <div class="card-body">
            <div class="setting-toggle" v-for="n in notifToggles" :key="n.key">
              <div>
                <p class="toggle-label">{{ n.label }}</p>
                <p class="toggle-desc text-muted text-sm">{{ n.desc }}</p>
              </div>
              <div class="toggle-wrap">
                <input type="checkbox" :id="n.key" v-model="n.value" class="toggle-input" />
                <label :for="n.key" class="toggle-label-el"></label>
              </div>
            </div>
            <div class="setting-divider"></div>
            <div class="form-group">
              <label class="form-label">Email From Address</label>
              <input v-model="notifications.from_email" type="email" class="form-control" />
            </div>
            <div class="form-group">
              <label class="form-label">SMS Provider</label>
              <select v-model="notifications.sms_provider" class="form-control">
                <option value="twilio">Twilio</option>
                <option value="nexmo">Nexmo / Vonage</option>
                <option value="aws_sns">AWS SNS</option>
              </select>
            </div>
          </div>
        </template>

        <!-- System -->
        <template v-if="activeTab === 'system'">
          <div class="card-header"><span class="card-title">System Configuration</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">AML Threshold ($)</label>
              <input v-model.number="system.aml_threshold" type="number" class="form-control" style="width:160px" />
              <p class="form-hint">Transactions above this amount trigger automatic AML screening.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Minimum Balance Penalty ($)</label>
              <input v-model.number="system.min_balance_penalty" type="number" class="form-control" style="width:160px" />
            </div>
            <div class="setting-divider"></div>
            <div class="flex gap-2" style="flex-wrap:wrap">
              <button class="btn btn-ghost" @click="clearCache">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                Clear Cache
              </button>
              <button class="btn btn-ghost" @click="runMaintenance">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                Run Maintenance
              </button>
              <button class="btn btn-ghost" @click="exportConfig">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:5px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Config
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'

const toast     = useToast()
const activeTab = ref('general')
const saving    = ref(false)
const savedMsg  = ref(false)

const tabs = [
  { key:'general',       label:'General',       iconPath:'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5' },
  { key:'security',      label:'Security',      iconPath:'M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z' },
  { key:'notifications', label:'Notifications', iconPath:'M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9 M13.73 21a2 2 0 01-3.46 0' },
  { key:'system',        label:'System',        iconPath:'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
]

const general = reactive({
  bank_name:     'Modern Bank Management System',
  short_name:    'MBMS',
  currency:      'USD',
  timezone:      'America/New_York',
  date_format:   'MM/DD/YYYY',
  contact_email: 'support@mbms.com',
})

const security = reactive({ max_attempts:5, session_timeout:60, hv_threshold:10000 })

const securityToggles = reactive([
  { key:'2fa_mandatory', label:'Enforce 2FA for Staff',    desc:'All staff must use two-factor authentication',  value:true },
  { key:'ip_whitelist',  label:'IP Whitelisting (Admins)', desc:'Restrict Super Admin / IT Admin to approved IPs',value:false },
  { key:'audit_log',     label:'Enable Audit Logging',     desc:'Log all user actions for compliance',           value:true },
  { key:'captcha',       label:'CAPTCHA on Login',         desc:'Show CAPTCHA after 3 failed login attempts',    value:true },
  { key:'force_pw',      label:'Force Password Expiry',    desc:'Require staff to reset password every 90 days', value:false },
])

const notifications = reactive({ from_email:'noreply@mbms.com', sms_provider:'twilio' })

const notifToggles = reactive([
  { key:'sms',       label:'SMS Notifications',   desc:'Send SMS for transactions and security alerts',  value:true },
  { key:'email',     label:'Email Notifications', desc:'Send emails for account activity',              value:true },
  { key:'aml_alert', label:'AML Alert Emails',    desc:'Notify compliance officer on new AML alerts',   value:true },
  { key:'eod_report',label:'EOD Summary Email',   desc:'Send end-of-day summary report by email',       value:false },
  { key:'kyc_update',label:'KYC Status Updates',  desc:'Notify customers when KYC status changes',      value:true },
])

const system = reactive({ aml_threshold:10000, min_balance_penalty:25 })

async function saveSettings() {
  saving.value = true
  await new Promise(r => setTimeout(r, 700))
  saving.value   = false
  savedMsg.value = true
  setTimeout(() => savedMsg.value = false, 3500)
}

function clearCache() { toast.success('Application cache cleared successfully.') }
function runMaintenance() { toast.info('Maintenance mode activated. Active sessions will be notified.') }
function exportConfig() {
  const config = { general, security: { ...security, toggles: securityToggles }, notifications: { ...notifications, toggles: notifToggles }, system }
  const blob = new Blob([JSON.stringify(config, null, 2)], { type:'application/json' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href = url; a.download = 'mbms-config.json'; a.click()
  URL.revokeObjectURL(url)
  toast.success('Configuration exported.')
}
</script>

<style scoped>
.settings-grid { display:grid; grid-template-columns:220px 1fr; gap:16px; align-items:start; }
.settings-nav  { padding:10px; }
.settings-nav-item { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:var(--radius-sm); font-size:.875rem; font-weight:600; color:var(--gray-500); cursor:pointer; transition:all .15s; }
.settings-nav-item.active { background:rgba(13,115,119,.1); color:var(--secondary); }
.settings-nav-item:hover:not(.active) { background:var(--gray-50); color:var(--gray-700); }
.settings-panel .card-body { max-width:580px; }
.setting-toggle { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid var(--gray-100); gap:16px; }
.setting-toggle:last-of-type { border-bottom:none; }
.toggle-label { font-size:.875rem; font-weight:600; color:var(--gray-800); }
.toggle-desc  { margin-top:2px; }
.toggle-wrap  { flex-shrink:0; }
.toggle-input { display:none; }
.toggle-label-el { display:block; width:44px; height:24px; background:var(--gray-300); border-radius:12px; cursor:pointer; position:relative; transition:background .2s; }
.toggle-label-el::after { content:''; position:absolute; top:2px; left:2px; width:20px; height:20px; background:white; border-radius:50%; transition:transform .2s; }
.toggle-input:checked + .toggle-label-el { background:var(--secondary); }
.toggle-input:checked + .toggle-label-el::after { transform:translateX(20px); }
.setting-divider { height:1px; background:var(--gray-100); margin:16px 0; }
.form-hint { font-size:.75rem; color:var(--gray-500); margin-top:4px; }
.save-banner { display:flex; align-items:center; gap:8px; background:#d1fae5; color:#065f46; border-radius:var(--radius); padding:10px 16px; margin-bottom:16px; font-size:.875rem; font-weight:600; }
@media (max-width:768px) { .settings-grid { grid-template-columns:1fr; } }
</style>
