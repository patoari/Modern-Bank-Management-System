<template>
  <div class="login-box">
    <div class="login-header">
      <h2>Welcome Back</h2>
      <p>Sign in to your account to continue</p>
    </div>

    <form @submit.prevent="handleLogin" class="login-form">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input
          v-model="form.email"
          type="email"
          class="form-control"
          :class="{ error: errors.email }"
          placeholder="you@bank.com"
          autocomplete="email"
          required
        />
        <span class="form-error" v-if="errors.email">{{ errors.email }}</span>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="password-wrap">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="form-control"
            :class="{ error: errors.password }"
            placeholder="••••••••••"
            autocomplete="current-password"
            required
          />
          <button type="button" class="eye-btn" @click="showPassword = !showPassword" :title="showPassword ? 'Hide password' : 'Show password'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px">
              <template v-if="showPassword">
                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </template>
              <template v-else>
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </template>
            </svg>
          </button>
        </div>
        <span class="form-error" v-if="errors.password">{{ errors.password }}</span>
      </div>

      <!-- 2FA -->
      <div class="form-group" v-if="requires2FA">
        <label class="form-label">Two-Factor Code</label>
        <input
          v-model="form.otp"
          type="text"
          class="form-control"
          placeholder="6-digit code"
          maxlength="6"
          autocomplete="one-time-code"
        />
      </div>

      <div class="login-options">
        <label class="checkbox-label">
          <input type="checkbox" v-model="form.remember" />
          <span>Remember this device</span>
        </label>
        <router-link to="/auth/forgot-password" class="forgot-link">Forgot password?</router-link>
      </div>

    <div class="login-error" v-if="loginError">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ loginError }}
    </div>

      <button type="submit" class="btn btn-primary w-full login-btn" :disabled="loading">
        <span v-if="loading" class="spinner-sm"></span>
        <span>{{ loading ? 'Signing in...' : 'Sign In' }}</span>
      </button>
    </form>

    <!-- Demo credentials -->
    <div class="demo-creds">
      <p class="demo-title">Demo Credentials</p>
      <div class="demo-grid">
        <button
          v-for="demo in demoCreds"
          :key="demo.role"
          class="demo-btn"
          @click="fillDemo(demo)"
        >
          {{ demo.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth   = useAuthStore()
const router = useRouter()
const route  = useRoute()
const toast  = useToast()

const loading      = ref(false)
const showPassword = ref(false)
const requires2FA  = ref(false)
const loginError   = ref('')

const form = reactive({ email: '', password: '', otp: '', remember: false })
const errors = reactive({ email: '', password: '' })

const demoCreds = [
  { role: 'super_admin',    label: 'Super Admin',    email: 'admin@bank.com',    password: 'Admin@1234567' },
  { role: 'branch_manager', label: 'Branch Manager', email: 'manager@bank.com',  password: 'Manager@123' },
  { role: 'teller',         label: 'Teller',         email: 'teller@bank.com',   password: 'Teller@123' },
  { role: 'customer',       label: 'Customer',       email: 'customer@bank.com', password: 'Customer@123' },
]

function fillDemo(demo) {
  form.email    = demo.email
  form.password = demo.password
  loginError.value = ''
}

async function handleLogin() {
  errors.email = errors.password = ''
  loginError.value = ''

  if (!form.email)    { errors.email = 'Email is required'; return }
  if (!form.password) { errors.password = 'Password is required'; return }

  loading.value = true
  try {
    await auth.login({ email: form.email, password: form.password, otp: form.otp, remember: form.remember })
    toast.success('Welcome back!')
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } catch (err) {
    const msg = err.response?.data?.message
    if (err.response?.status === 403 && msg?.includes('2FA')) {
      requires2FA.value = true
      loginError.value = 'Please enter your two-factor authentication code.'
    } else {
      loginError.value = msg || 'Invalid email or password. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-box {
  background: var(--white);
  border-radius: var(--radius-lg);
  padding: 40px;
  width: 100%;
  max-width: 420px;
  box-shadow: var(--shadow-lg);
}
.login-header { text-align: center; margin-bottom: 32px; }
.login-header h2 { font-size: 1.6rem; font-weight: 800; color: var(--gray-900); margin-bottom: 6px; }
.login-header p  { color: var(--gray-500); font-size: .9rem; }

.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 42px; }
.eye-btn {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; font-size: 1rem; color: var(--gray-400);
}

.login-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.checkbox-label { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--gray-600); cursor: pointer; }
.forgot-link    { font-size: .85rem; color: var(--secondary); font-weight: 600; }
.forgot-link:hover { text-decoration: underline; }

.login-error {
  background: #fee2e2;
  color: var(--danger);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-size: .85rem;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.login-btn { justify-content: center; padding: 11px; font-size: .95rem; margin-bottom: 4px; }

.spinner-sm {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

.demo-creds {
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid var(--gray-100);
}
.demo-title {
  text-align: center;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--gray-400);
  margin-bottom: 10px;
}
.demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.demo-btn {
  padding: 7px 10px;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-sm);
  background: var(--gray-50);
  font-size: .78rem;
  color: var(--gray-600);
  transition: all .15s;
}
.demo-btn:hover { border-color: var(--secondary); color: var(--secondary); background: rgba(13,115,119,.05); }
</style>
