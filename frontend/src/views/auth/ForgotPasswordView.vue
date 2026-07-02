<template>
  <div class="login-box">
    <div class="login-header">
      <h2>Reset Password</h2>
      <p>Enter your email address to receive a reset link</p>
    </div>

    <div v-if="!sent">
      <form @submit.prevent="handleSubmit" class="login-form">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input v-model="email" type="email" class="form-control" placeholder="you@bank.com" required autocomplete="email" />
        </div>
        <button type="submit" class="btn btn-primary w-full login-btn" :disabled="loading">
          <span v-if="loading" class="spinner-sm"></span>
          <span>{{ loading ? 'Sending...' : 'Send Reset Link' }}</span>
        </button>
      </form>
    </div>

    <div v-else class="sent-msg">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--success);margin:0 auto 16px;display:block">
        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <p style="font-size:.95rem;font-weight:600;color:var(--gray-800);margin-bottom:8px">Check your email</p>
      <p style="font-size:.85rem;color:var(--gray-500)">If an account exists for <strong>{{ email }}</strong>, a password reset link has been sent.</p>
    </div>

    <div style="text-align:center;margin-top:24px">
      <router-link to="/auth/login" class="forgot-link">← Back to Sign In</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const email   = ref('')
const loading = ref(false)
const sent    = ref(false)

async function handleSubmit() {
  if (!email.value) return
  loading.value = true
  // Simulate API call
  await new Promise(r => setTimeout(r, 1200))
  loading.value = false
  sent.value    = true
}
</script>

<style scoped>
.login-box { background:var(--white); border-radius:var(--radius-lg); padding:40px; width:100%; max-width:420px; box-shadow:var(--shadow-lg); }
.login-header { text-align:center; margin-bottom:28px; }
.login-header h2 { font-size:1.6rem; font-weight:800; color:var(--gray-900); margin-bottom:6px; }
.login-header p  { color:var(--gray-500); font-size:.9rem; }
.login-btn { justify-content:center; padding:11px; font-size:.95rem; display:flex; align-items:center; gap:8px; width:100%; }
.spinner-sm { width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite; }
.sent-msg { text-align:center; padding:16px 0; }
.forgot-link { font-size:.875rem; color:var(--secondary); font-weight:600; }
.forgot-link:hover { text-decoration:underline; }
</style>
