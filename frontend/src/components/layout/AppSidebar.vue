<template>
  <aside class="sidebar" :class="{ collapsed: ui.sidebarCollapsed }">
    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="brand-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9.5L12 4l9 5.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/>
          <rect x="9" y="14" width="6" height="7" rx="0.5"/>
        </svg>
      </div>
      <span class="brand-name">MBMS</span>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
      <template v-for="group in filteredMenu" :key="group.label">
        <div class="nav-group-label">{{ group.label }}</div>
        <router-link
          v-for="item in group.items"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          :class="{ active: isActive(item.to) }"
        >
          <span class="nav-icon" v-html="item.icon"></span>
          <span class="nav-label">{{ item.label }}</span>
          <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
        </router-link>
      </template>
    </nav>

    <!-- Bottom -->
    <div class="sidebar-bottom">
      <button class="collapse-btn" @click="ui.toggleCollapse()" :title="ui.sidebarCollapsed ? 'Expand' : 'Collapse'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="collapse-icon" :class="{ rotated: ui.sidebarCollapsed }">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const ui   = useUiStore()
const auth = useAuthStore()
const route = useRoute()

// All icons are inline SVG — Heroicons outline style (24px grid, 1.75 stroke)
const icons = {
  dashboard: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
  </svg>`,

  customers: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
  </svg>`,

  accounts: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <rect x="2" y="5" width="20" height="14" rx="2"/>
    <path d="M2 10h20"/>
    <path d="M6 15h2M10 15h4"/>
  </svg>`,

  transactions: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M7 16V4m0 0L3 8m4-4l4 4"/>
    <path d="M17 8v12m0 0l4-4m-4 4l-4-4"/>
  </svg>`,

  loans: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 2v6m0 0l3-3m-3 3L9 5"/>
    <path d="M3 11h18M5 11V19a2 2 0 002 2h10a2 2 0 002-2v-8"/>
    <path d="M9 15h6"/>
  </svg>`,

  cards: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <rect x="2" y="5" width="20" height="14" rx="2"/>
    <path d="M2 10h20"/>
    <circle cx="6" cy="15" r="1" fill="currentColor"/>
    <circle cx="9" cy="15" r="1" fill="currentColor"/>
  </svg>`,

  deposits: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 2a7 7 0 017 7c0 4-7 13-7 13S5 13 5 9a7 7 0 017-7z"/>
    <circle cx="12" cy="9" r="2.5"/>
  </svg>`,

  branches: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 9.5L12 4l9 5.5"/>
    <path d="M3 9.5V20h18V9.5"/>
    <rect x="9" y="14" width="6" height="6" rx="0.5"/>
    <path d="M9 20v-6h6v6"/>
  </svg>`,

  reports: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18 20V10M12 20V4M6 20v-6"/>
  </svg>`,

  compliance: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7l-9-5z"/>
    <path d="M9 12l2 2 4-4"/>
  </svg>`,

  audit: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
    <path d="M14 2v6h6"/>
    <path d="M16 13H8M16 17H8M10 9H8"/>
  </svg>`,

  users: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="8" r="4"/>
    <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/>
    <path d="M16 3.13a4 4 0 010 7.75"/>
    <path d="M20 21v-1a4 4 0 00-3-3.87"/>
  </svg>`,

  settings: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="3"/>
    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
  </svg>`,
}

const menu = [
  {
    label: 'Overview',
    roles: null,
    items: [
      { to: '/dashboard',    icon: icons.dashboard,    label: 'Dashboard' },
    ]
  },
  {
    label: 'Core Banking',
    roles: null,
    items: [
      { to: '/customers',    icon: icons.customers,    label: 'Customers' },
      { to: '/accounts',     icon: icons.accounts,     label: 'Accounts' },
      { to: '/transactions', icon: icons.transactions, label: 'Transactions' },
    ]
  },
  {
    label: 'Products',
    roles: null,
    items: [
      { to: '/loans',        icon: icons.loans,        label: 'Loans & Credit' },
      { to: '/cards',        icon: icons.cards,        label: 'Cards' },
      { to: '/deposits',     icon: icons.deposits,     label: 'FD / RD' },
    ]
  },
  {
    label: 'Operations',
    roles: ['super_admin','bank_admin','branch_manager','accountant'],
    items: [
      { to: '/branches',     icon: icons.branches,     label: 'Branches & ATMs' },
      { to: '/reports',      icon: icons.reports,      label: 'Reports' },
    ]
  },
  {
    label: 'Compliance',
    roles: ['super_admin','bank_admin','compliance_officer','auditor'],
    items: [
      { to: '/compliance',   icon: icons.compliance,   label: 'AML / Compliance' },
      { to: '/audit-logs',   icon: icons.audit,        label: 'Audit Logs' },
    ]
  },
  {
    label: 'Administration',
    roles: ['super_admin','bank_admin','it_admin'],
    items: [
      { to: '/users',        icon: icons.users,        label: 'Users & Roles' },
      { to: '/settings',     icon: icons.settings,     label: 'Settings' },
    ]
  },
]

const filteredMenu = computed(() =>
  menu.filter(g => !g.roles || !auth.userRole || g.roles.includes(auth.userRole))
)

function isActive(path) {
  return route.path.startsWith(path) && path !== '/'
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--primary-dark);
  color: white;
  display: flex;
  flex-direction: column;
  transition: width .25s ease;
  overflow: hidden;
  z-index: 100;
}
.sidebar.collapsed { width: var(--sidebar-collapsed-w); }

/* Brand */
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}
.brand-icon-wrap {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  color: var(--accent, #0d7377);
  display: flex;
  align-items: center;
  justify-content: center;
}
.brand-icon-wrap svg { width: 100%; height: 100%; }
.brand-name {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--accent, #0d7377);
  letter-spacing: .1em;
  white-space: nowrap;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.1) transparent;
}
.nav-group-label {
  font-size: .6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .12em;
  color: rgba(255,255,255,.3);
  padding: 16px 20px 5px;
  white-space: nowrap;
  overflow: hidden;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 8px 20px;
  color: rgba(255,255,255,.55);
  transition: color .15s, background .15s;
  border-left: 2px solid transparent;
  white-space: nowrap;
  font-size: .855rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
}
.nav-item:hover {
  color: rgba(255,255,255,.9);
  background: rgba(255,255,255,.05);
}
.nav-item.active {
  color: white;
  background: rgba(13,115,119,.25);
  border-left-color: var(--secondary, #14a9ae);
}

/* SVG icons inside nav */
.nav-icon {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.nav-icon :deep(svg) {
  width: 18px;
  height: 18px;
  stroke: currentColor;
}

.nav-label { flex: 1; }
.nav-badge {
  background: var(--danger, #e53e3e);
  color: white;
  font-size: .6rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 10px;
}

/* Bottom collapse button */
.sidebar-bottom {
  padding: 12px 20px;
  border-top: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}
.collapse-btn {
  background: rgba(255,255,255,.07);
  border: none;
  color: rgba(255,255,255,.6);
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background .15s, color .15s;
}
.collapse-btn:hover { background: rgba(255,255,255,.13); color: white; }
.collapse-icon {
  width: 15px;
  height: 15px;
  transition: transform .25s ease;
}
.collapse-icon.rotated { transform: rotate(180deg); }

/* Collapsed state */
.sidebar.collapsed .brand-name,
.sidebar.collapsed .nav-group-label,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-badge { display: none; }
.sidebar.collapsed .nav-item { justify-content: center; padding: 10px; border-left-color: transparent; }
.sidebar.collapsed .nav-item.active { background: rgba(13,115,119,.25); border-radius: 6px; margin: 0 8px; }
.sidebar.collapsed .sidebar-bottom { display: flex; justify-content: center; padding: 12px; }
</style>
