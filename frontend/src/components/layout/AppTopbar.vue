<template>
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-btn" @click="ui.toggleCollapse()" title="Toggle sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
          <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
      </button>
      <div class="breadcrumb">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="bc-home-icon">
          <path d="M3 9.5L12 4l9 5.5V20h-6v-6H9v6H3V9.5z"/>
        </svg>
        <span class="bc-sep">/</span>
        <span class="bc-current">{{ pageTitle }}</span>
      </div>
    </div>

    <div class="topbar-right">
      <!-- Search -->
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
          <circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" placeholder="Search customers, accounts..." v-model="searchQuery" @keyup.enter="handleSearch" />
      </div>

      <!-- Notifications -->
      <div class="notif-wrap" ref="notifRef">
        <button class="icon-btn" @click="toggleNotif" title="Notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span class="notif-dot" v-if="notifCount > 0">{{ notifCount }}</span>
        </button>
        <transition name="fade">
          <div class="notif-dropdown" v-if="showNotifs">
            <div class="notif-header">
              <span style="font-weight:700;font-size:.9rem">Notifications</span>
              <button class="btn btn-ghost btn-sm" style="font-size:.75rem;padding:3px 8px" @click="markAllRead">Mark all read</button>
            </div>
            <div class="notif-list">
              <div v-for="n in notifications" :key="n.id" class="notif-item" :class="{ unread: !n.read }">
                <span class="notif-dot-sm" :class="'dot-' + n.type"></span>
                <div>
                  <p class="notif-title">{{ n.title }}</p>
                  <p class="notif-msg">{{ n.msg }}</p>
                  <p class="notif-time">{{ n.time }}</p>
                </div>
              </div>
            </div>
            <div class="notif-footer">
              <router-link to="/audit-logs" class="notif-footer-link" @click="showNotifs=false">View all activity →</router-link>
            </div>
          </div>
        </transition>
      </div>

      <!-- User Menu -->
      <div class="user-menu" ref="userMenuRef">
        <button class="user-btn" @click="showUserMenu = !showUserMenu">
          <div class="user-avatar">{{ initials }}</div>
          <div class="user-info">
            <span class="user-name">{{ auth.userName || 'User' }}</span>
            <span class="user-role">{{ roleLabel }}</span>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--gray-400)">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </button>
        <transition name="fade">
          <div class="dropdown" v-if="showUserMenu">
            <router-link to="/profile" class="dropdown-item" @click="showUserMenu = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/>
              </svg>
              My Profile
            </router-link>
            <router-link to="/settings" class="dropdown-item" @click="showUserMenu = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
              </svg>
              Settings
            </router-link>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item text-danger" @click="handleLogout">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              Sign Out
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const ui     = useUiStore()
const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const searchQuery  = ref('')
const showUserMenu = ref(false)
const notifCount   = ref(3)
const showNotifs   = ref(false)
const userMenuRef  = ref(null)
const notifRef     = ref(null)

const notifications = [
  { id:1, type:'danger',  title:'AML Alert',         msg:'Flag raised on account ****9983', time:'5 min ago', read:false },
  { id:2, type:'warning', title:'Overdue EMIs',       msg:'3 loan EMIs overdue this week',   time:'1 hr ago',  read:false },
  { id:3, type:'info',    title:'Maintenance',        msg:'Scheduled at 2:00 AM tonight',    time:'2 hrs ago', read:false },
]

const pageTitle = computed(() => {
  const map = {
    '/dashboard': 'Dashboard',
    '/customers': 'Customers',
    '/accounts': 'Accounts',
    '/transactions': 'Transactions',
    '/loans': 'Loans & Credit',
    '/cards': 'Cards',
    '/deposits': 'Fixed / Recurring Deposits',
    '/branches': 'Branches & ATMs',
    '/reports': 'Reports',
    '/compliance': 'AML & Compliance',
    '/audit-logs': 'Audit Logs',
    '/users': 'Users & Roles',
    '/settings': 'Settings',
    '/profile': 'My Profile'
  }
  return map[route.path] ?? route.path.split('/').pop()?.replace(/-/g, ' ')
})

const initials = computed(() => {
  const name = auth.userName || 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0,2)
})

const roleLabel = computed(() => {
  const map = {
    super_admin: 'Super Admin', bank_admin: 'Bank Admin',
    branch_manager: 'Branch Manager', teller: 'Teller',
    customer: 'Customer', loan_officer: 'Loan Officer',
    credit_manager: 'Credit Manager', compliance_officer: 'Compliance Officer',
    auditor: 'Auditor', it_admin: 'IT Admin', accountant: 'Accountant'
  }
  return map[auth.userRole] ?? auth.userRole ?? 'Staff'
})

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.push({ name: 'customers', query: { search: searchQuery.value } })
  }
}

function toggleNotif() {
  showNotifs.value = !showNotifs.value
  showUserMenu.value = false
}

function markAllRead() {
  notifications.forEach(n => n.read = true)
  notifCount.value = 0
}

async function handleLogout() {
  showUserMenu.value = false
  await auth.logout()
}

// Close dropdown on outside click
function handleClickOutside(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) showUserMenu.value = false
  if (notifRef.value && !notifRef.value.contains(e.target)) showNotifs.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.topbar {
  height: var(--topbar-h);
  background: var(--white);
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 50;
  flex-shrink: 0;
}
.topbar-left, .topbar-right { display: flex; align-items: center; gap: 12px; }

.menu-btn {
  background: none; border: none;
  font-size: 1.1rem; color: var(--gray-600);
  padding: 6px; border-radius: var(--radius-sm);
  transition: background .15s;
}
.menu-btn:hover { background: var(--gray-100); }

.breadcrumb { display: flex; align-items: center; gap: 6px; font-size: .85rem; color: var(--gray-500); }
.bc-home-icon { width:15px; height:15px; color:var(--gray-400); }
.bc-sep      { color: var(--gray-300); }
.bc-current  { color: var(--gray-800); font-weight: 600; text-transform: capitalize; }

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-sm);
  padding: 6px 12px;
  min-width: 240px;
}
.search-box input {
  border: none;
  background: none;
  font-size: .85rem;
  color: var(--gray-700);
  outline: none;
  width: 100%;
}
.search-icon { color: var(--gray-400); font-size: .85rem; }

.icon-btn {
  background: none; border: none;
  font-size: 1.1rem;
  padding: 6px;
  border-radius: var(--radius-sm);
  color: var(--gray-600);
  position: relative;
  transition: background .15s;
}
.icon-btn:hover { background: var(--gray-100); }
.notif-dot {
  position: absolute;
  top: 2px; right: 2px;
  background: var(--danger);
  color: white;
  font-size: .55rem;
  font-weight: 700;
  width: 16px; height: 16px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}

.user-menu { position: relative; }
.user-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  padding: 6px 10px;
  border-radius: var(--radius-sm);
  transition: background .15s;
  color: var(--gray-700);
}
.user-btn:hover { background: var(--gray-100); }
.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: .8rem; font-weight: 700;
  flex-shrink: 0;
}
.user-info { text-align: left; }
.user-name { display: block; font-size: .85rem; font-weight: 700; color: var(--gray-800); }
.user-role { display: block; font-size: .72rem; color: var(--gray-500); }

.dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  min-width: 180px;
  padding: 6px;
  z-index: 200;
}
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  font-size: .875rem;
  color: var(--gray-700);
  transition: background .15s;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}
.dropdown-item:hover  { background: var(--gray-50); }
.text-danger          { color: var(--danger) !important; }
.dropdown-divider     { height: 1px; background: var(--gray-100); margin: 4px 0; }

/* Notification dropdown */
.notif-wrap { position: relative; }
.notif-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  background: var(--white); border: 1px solid var(--gray-200);
  border-radius: var(--radius); box-shadow: var(--shadow-lg);
  width: 320px; z-index: 200;
}
.notif-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border-bottom: 1px solid var(--gray-100);
}
.notif-list { max-height: 280px; overflow-y: auto; }
.notif-item {
  display: flex; gap: 10px; align-items: flex-start;
  padding: 12px 16px; border-bottom: 1px solid var(--gray-50);
  transition: background .12s;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: var(--gray-50); }
.notif-item.unread { background: rgba(13,115,119,.03); }
.notif-dot-sm { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px; }
.dot-danger  { background: var(--danger); }
.dot-warning { background: var(--warning); }
.dot-info    { background: var(--info,#3b82f6); }
.dot-success { background: var(--success); }
.notif-title { font-size:.8rem; font-weight:700; color:var(--gray-800); }
.notif-msg   { font-size:.75rem; color:var(--gray-600); margin-top:1px; }
.notif-time  { font-size:.7rem; color:var(--gray-400); margin-top:3px; }
.notif-footer { padding:10px 16px; border-top:1px solid var(--gray-100); text-align:center; }
.notif-footer-link { font-size:.8rem; color:var(--secondary); font-weight:600; }
.notif-footer-link:hover { text-decoration:underline; }
</style>
