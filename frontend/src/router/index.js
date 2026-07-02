import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts
import AuthLayout from '@/layouts/AuthLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

// Auth pages
import LoginView from '@/views/auth/LoginView.vue'

// Dashboard pages (lazy loaded)
const DashboardView       = () => import('@/views/dashboard/DashboardView.vue')
const CustomersView       = () => import('@/views/customers/CustomersView.vue')
const CustomerDetailView  = () => import('@/views/customers/CustomerDetailView.vue')
const AccountsView        = () => import('@/views/accounts/AccountsView.vue')
const AccountDetailView   = () => import('@/views/accounts/AccountDetailView.vue')
const TransactionsView    = () => import('@/views/transactions/TransactionsView.vue')
const LoansView           = () => import('@/views/loans/LoansView.vue')
const LoanDetailView      = () => import('@/views/loans/LoanDetailView.vue')
const CardsView           = () => import('@/views/cards/CardsView.vue')
const DepositsView        = () => import('@/views/deposits/DepositsView.vue')
const BranchesView        = () => import('@/views/branches/BranchesView.vue')
const UsersView           = () => import('@/views/users/UsersView.vue')
const ReportsView         = () => import('@/views/reports/ReportsView.vue')
const AuditLogsView       = () => import('@/views/audit/AuditLogsView.vue')
const ComplianceView      = () => import('@/views/compliance/ComplianceView.vue')
const SettingsView        = () => import('@/views/settings/SettingsView.vue')
const ProfileView         = () => import('@/views/profile/ProfileView.vue')
const NotFoundView        = () => import('@/views/NotFoundView.vue')

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      { path: 'login',           name: 'login',            component: LoginView },
      { path: 'forgot-password', name: 'forgot-password',  component: () => import('@/views/auth/ForgotPasswordView.vue') },
    ]
  },
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard',        name: 'dashboard',        component: DashboardView },
      { path: 'customers',        name: 'customers',        component: CustomersView },
      { path: 'customers/:id',    name: 'customer-detail',  component: CustomerDetailView },
      { path: 'accounts',         name: 'accounts',         component: AccountsView },
      { path: 'accounts/:id',     name: 'account-detail',   component: AccountDetailView },
      { path: 'transactions',     name: 'transactions',     component: TransactionsView },
      { path: 'loans',            name: 'loans',            component: LoansView },
      { path: 'loans/:id',        name: 'loan-detail',      component: LoanDetailView },
      { path: 'cards',            name: 'cards',            component: CardsView },
      { path: 'deposits',         name: 'deposits',         component: DepositsView },
      { path: 'branches',         name: 'branches',         component: BranchesView },
      { path: 'users',            name: 'users',            component: UsersView },
      { path: 'reports',          name: 'reports',          component: ReportsView },
      { path: 'audit-logs',       name: 'audit-logs',       component: AuditLogsView },
      { path: 'compliance',       name: 'compliance',       component: ComplianceView },
      { path: 'settings',         name: 'settings',         component: SettingsView },
      { path: 'profile',          name: 'profile',          component: ProfileView }
    ]
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.name === 'login' && auth.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
