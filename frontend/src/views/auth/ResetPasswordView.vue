<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo/Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">Bank Management System</h1>
        <p class="text-blue-100">Reset Your Password</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-lg shadow-xl p-8">
        <!-- Email Input -->
        <div v-if="step === 'email'" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
            <input
              v-model="email"
              type="email"
              placeholder="your@email.com"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <button
            @click="sendResetLink"
            :disabled="loading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
          >
            {{ loading ? 'Sending...' : 'Send Reset Link' }}
          </button>

          <p class="text-sm text-gray-600 text-center">
            Back to
            <router-link to="/auth/login" class="text-blue-600 hover:underline">Login</router-link>
          </p>
        </div>

        <!-- Reset Token & Password Input -->
        <div v-else-if="step === 'reset'" class="space-y-4">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p class="text-green-800 text-sm">
              ✓ Check your email for the password reset link. Enter the code below.
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reset Code</label>
            <input
              v-model="token"
              type="text"
              placeholder="Enter 60-character code"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input
              v-model="password"
              type="password"
              placeholder="Min 10 chars, 1 uppercase, 1 number, 1 symbol"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="text-xs text-gray-500 mt-1">
              Password must contain uppercase, number, and symbol
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
            <input
              v-model="passwordConfirm"
              type="password"
              placeholder="Confirm password"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <button
            @click="resetPassword"
            :disabled="loading || password !== passwordConfirm"
            class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 disabled:opacity-50 transition"
          >
            {{ loading ? 'Resetting...' : 'Reset Password' }}
          </button>

          <p class="text-sm text-gray-600 text-center">
            <button @click="step = 'email'" class="text-blue-600 hover:underline">
              Send another code
            </button>
          </p>
        </div>

        <!-- Success Message -->
        <div v-else class="text-center space-y-4">
          <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="text-4xl mb-2">✓</div>
            <h3 class="text-lg font-semibold text-green-800 mb-2">Password Reset Successful</h3>
            <p class="text-green-700 text-sm mb-4">
              Your password has been reset successfully. You can now login with your new password.
            </p>
          </div>

          <router-link
            to="/auth/login"
            class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
          >
            Back to Login
          </router-link>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
          <p class="text-red-800 text-sm">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const router = useRouter()
const toast = useToast()

const step = ref('email')
const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const error = ref('')

const sendResetLink = async () => {
  if (!email.value) {
    error.value = 'Please enter your email'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await axios.post('/api/v1/auth/forgot-password', {
      email: email.value,
    })

    toast.success('Check your email for the reset link')
    step.value = 'reset'
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to send reset link'
  } finally {
    loading.value = false
  }
}

const resetPassword = async () => {
  if (!token.value || !password.value || !passwordConfirm.value) {
    error.value = 'Please fill in all fields'
    return
  }

  if (password.value !== passwordConfirm.value) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await axios.post('/api/v1/auth/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirm.value,
    })

    toast.success('Password reset successfully!')
    step.value = 'success'
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to reset password'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
input:focus {
  outline: none;
}
</style>
