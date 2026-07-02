<template>
  <div>
    <div class="page-header">
      <div><h1 class="page-title">Cards</h1><p class="page-subtitle">Manage debit, credit, and prepaid cards</p></div>
      <button class="btn btn-primary" @click="showModal = true">+ Issue Card</button>
    </div>

    <div class="cards-grid">
      <div class="card-visual" v-for="card in cards" :key="card.id">
        <div class="card-chip"><div class="chip-inner"></div></div>
        <div class="card-number">{{ maskCard(card.number) }}</div>
        <div class="card-footer-row">
          <div><p class="card-label">Card Holder</p><p class="card-val">{{ card.holder }}</p></div>
          <div><p class="card-label">Expires</p><p class="card-val">{{ card.expiry }}</p></div>
          <div><p class="card-label">Network</p><p class="card-val">{{ card.network }}</p></div>
        </div>
        <div class="card-type-badge">{{ card.type }}</div>
        <div class="card-actions">
          <span class="badge" :class="card.status === 'active' ? 'badge-success' : 'badge-danger'">{{ card.status }}</span>
          <button class="btn btn-ghost btn-sm" @click="toggleBlock(card)">
            <svg v-if="card.status === 'active'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 019.9-1"/></svg>
            {{ card.status === 'active' ? 'Block' : 'Unblock' }}
          </button>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:24px">
      <div class="card-header"><span class="card-title">All Cards</span></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Card Number</th><th>Type</th><th>Holder</th><th>Network</th><th>Account</th><th>Expiry</th><th>Daily Limit</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="c in cards" :key="c.id">
              <td class="mono text-sm">{{ maskCard(c.number) }}</td>
              <td><span class="badge badge-primary">{{ c.type }}</span></td>
              <td>{{ c.holder }}</td>
              <td>{{ c.network }}</td>
              <td class="mono text-xs">{{ c.account }}</td>
              <td>{{ c.expiry }}</td>
              <td>${{ c.daily_limit.toLocaleString() }}</td>
              <td><span class="badge" :class="c.status === 'active' ? 'badge-success' : 'badge-danger'">{{ c.status }}</span></td>
              <td>
                <div class="flex gap-1">
                  <button class="btn btn-ghost btn-sm" @click="editCard(c)">Manage</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Issue Card Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Issue New Card</h3>
              <button class="btn btn-icon btn-ghost" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group"><label class="form-label">Card Holder Name *</label><input v-model="form.holder" type="text" class="form-control" placeholder="Full name as on card" /></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                  <label class="form-label">Card Type *</label>
                  <select v-model="form.type" class="form-control">
                    <option value="">Select type</option>
                    <option value="Debit">Debit</option>
                    <option value="Credit">Credit</option>
                    <option value="Prepaid">Prepaid</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Network *</label>
                  <select v-model="form.network" class="form-control">
                    <option value="">Select network</option>
                    <option>Visa</option><option>Mastercard</option><option>Rupay</option><option>Amex</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Linked Account *</label>
                  <input v-model="form.account" type="text" class="form-control" placeholder="Account number" />
                </div>
                <div class="form-group">
                  <label class="form-label">Daily Limit ($)</label>
                  <input v-model="form.daily_limit" type="number" class="form-control" placeholder="5000" min="100" />
                </div>
              </div>
              <div v-if="formError" class="form-err">{{ formError }}</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="closeModal">Cancel</button>
              <button class="btn btn-primary" @click="issueCard" :disabled="saving">
                {{ saving ? 'Issuing...' : 'Issue Card' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Manage Card Modal -->
    <teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="showManageModal" @click.self="showManageModal=false">
          <div class="modal">
            <div class="modal-header">
              <h3 style="font-weight:700">Manage Card — {{ selectedCard ? maskCard(selectedCard.number) : '' }}</h3>
              <button class="btn btn-icon btn-ghost" @click="showManageModal=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body" v-if="selectedCard">
              <div class="form-group">
                <label class="form-label">Daily Limit ($)</label>
                <input v-model="manageForm.daily_limit" type="number" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Card Status</label>
                <select v-model="manageForm.status" class="form-control">
                  <option value="active">Active</option>
                  <option value="blocked">Blocked</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost" @click="showManageModal=false">Cancel</button>
              <button class="btn btn-primary" @click="saveManage">Save Changes</button>
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
const showModal       = ref(false)
const showManageModal = ref(false)
const saving          = ref(false)
const formError       = ref('')
const selectedCard    = ref(null)

const form       = reactive({ holder:'', type:'', network:'', account:'', daily_limit:'' })
const manageForm = reactive({ daily_limit:0, status:'' })

const cards = ref([
  { id:1, number:'4111111111114521', type:'Debit',   holder:'Alice Johnson',  network:'Visa',       account:'****5218', expiry:'12/27', daily_limit:5000,  status:'active' },
  { id:2, number:'5500005555554444', type:'Credit',  holder:'Alice Johnson',  network:'Mastercard', account:'****5218', expiry:'08/26', daily_limit:20000, status:'active' },
  { id:3, number:'4012888888881881', type:'Debit',   holder:'Bob Williams',   network:'Visa',       account:'****3341', expiry:'03/28', daily_limit:3000,  status:'blocked' },
  { id:4, number:'6011000990139424', type:'Prepaid', holder:'David Martinez', network:'Discover',   account:'****9910', expiry:'06/26', daily_limit:1000,  status:'active' },
])

function maskCard(n) { return n.replace(/(\d{4})(\d{4})(\d{4})(\d{4})/, '$1 **** **** $4') }

function toggleBlock(card) {
  card.status = card.status === 'active' ? 'blocked' : 'active'
  toast.success(`Card ${card.status === 'active' ? 'unblocked' : 'blocked'} successfully.`)
}

function editCard(card) {
  selectedCard.value = card
  manageForm.daily_limit = card.daily_limit
  manageForm.status = card.status
  showManageModal.value = true
}

function saveManage() {
  if (selectedCard.value) {
    selectedCard.value.daily_limit = parseFloat(manageForm.daily_limit)
    selectedCard.value.status = manageForm.status
    toast.success('Card settings updated.')
  }
  showManageModal.value = false
}

function closeModal() {
  showModal.value = false
  formError.value = ''
  Object.assign(form, { holder:'', type:'', network:'', account:'', daily_limit:'' })
}

async function issueCard() {
  formError.value = ''
  if (!form.holder.trim())  { formError.value = 'Card holder name is required.'; return }
  if (!form.type)           { formError.value = 'Please select a card type.'; return }
  if (!form.network)        { formError.value = 'Please select a network.'; return }
  if (!form.account.trim()) { formError.value = 'Linked account is required.'; return }

  saving.value = true
  await new Promise(r => setTimeout(r, 800))

  const prefixes = { Visa:'4', Mastercard:'5', Rupay:'6', Amex:'3' }
  const prefix   = prefixes[form.network] || '4'
  const number   = prefix + String(Math.floor(Math.random()*1e15)).slice(0, 15)
  const expYear  = new Date().getFullYear() + 5
  const expMonth = String(new Date().getMonth() + 1).padStart(2,'0')

  cards.value.unshift({
    id:          Date.now(),
    number,
    type:        form.type,
    holder:      form.holder,
    network:     form.network,
    account:     '****' + form.account.slice(-4),
    expiry:      `${expMonth}/${String(expYear).slice(-2)}`,
    daily_limit: parseFloat(form.daily_limit) || 5000,
    status:      'inactive',
  })

  saving.value = false
  closeModal()
  toast.success(`${form.type} card issued to ${form.holder}. Activation required.`)
}
</script>

<style scoped>
.cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin-bottom:8px; }
.card-visual { background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 50%,var(--secondary) 100%); border-radius:16px; padding:24px; color:white; position:relative; overflow:hidden; min-height:180px; display:flex; flex-direction:column; justify-content:space-between; }
.card-visual::before { content:''; position:absolute; top:-40px; right:-40px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,.05); }
.card-chip { margin-bottom:16px; }
.chip-inner { width:36px; height:28px; border-radius:4px; background:linear-gradient(135deg,#ffd166,#f0a500); }
.card-number { font-size:1.1rem; letter-spacing:.15em; font-weight:600; margin-bottom:16px; font-family:monospace; }
.card-footer-row { display:flex; gap:24px; }
.card-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.08em; opacity:.6; }
.card-val   { font-size:.82rem; font-weight:700; }
.card-type-badge { position:absolute; top:16px; right:16px; background:rgba(255,255,255,.15); padding:3px 10px; border-radius:10px; font-size:.7rem; font-weight:700; letter-spacing:.06em; }
.card-actions { display:flex; align-items:center; justify-content:space-between; margin-top:12px; }
.card-actions .btn { background:rgba(255,255,255,.15); color:white; border:1px solid rgba(255,255,255,.2); display:flex; align-items:center; }
.card-actions .btn:hover { background:rgba(255,255,255,.25); }
.mono { font-family:monospace; }
.form-err { background:#fee2e2; color:var(--danger); border-radius:var(--radius-sm); padding:8px 12px; font-size:.84rem; margin-top:4px; }
</style>
