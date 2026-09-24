<script setup>
import { ref, computed } from 'vue';
import api  from '@/api/index.js';
import { LockClosedIcon } from '@heroicons/vue/24/outline';

const email   = ref('')
const loading = ref(false)
const sent    = ref(false)
const error   = ref('')

const emailValid = computed(() =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())
)

async function handleSubmit() {
  if (!emailValid.value || loading.value) return
  loading.value = true
  error.value   = ''

  try {
    await api.post('/admin/forgot-password', { email: email.value.trim() })
    sent.value = true
  } catch {
    // Always show success to prevent email enumeration
    sent.value = true
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4">
    <div class="w-full max-w-md">

      <div class="text-center mb-8">
        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center
                    justify-center mx-auto mb-4">
          <LockClosedIcon class="w-6 h-6 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-white">Reset Password</h1>
        <p class="text-slate-400 mt-1 text-sm">
          Enter your email to receive a reset link
        </p>
      </div>

      <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">

        <!-- Success state -->
         <div v-if="sent" class="text-center">
          <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center
                      justify-center mx-auto mb-4">
            <span class="text-green-400 text-2xl">✓</span>
          </div>
          <h3 class="text-white font-semibold mb-2">Check Your Email</h3>
          <p class="text-slate-400 text-sm mb-6">
            If the provided email address is registered, you'll receive a password
            reset link shortly. Check your spam folder if you don't see it.
          </p>
          <router-link
            to="/admin/login"
            class="text-indigo-400 hover:text-indigo-300 text-sm transition-colors"
          >
            Back to login
          </router-link>
         </div>

         <!-- Form -->
          <template v-else>
            <div class="mb-5">
              <label class="block text-sm font-medium text-slate-300 mb-1">
                Email Address
              </label>
              <input
                v-model="email"
                type="email"
                placeholder="admin@portfolio.test"
                @keyup.enter="handleSubmit"
                class="w-full bg-slate-900 border border-slate-600 rounded-xl
                        px-4 py-3 text-white placeholder-slate-500
                        focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <button
              @click="handleSubmit"
              :disabled="!emailValid || loading"
              :class="['w-full py-3 rounded-xl font-medium text-sm transition-all',
                emailValid && !loading
                  ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                  : 'bg-slate-700 text-slate-500 cursor-not-allowed']"
            >
              {{ loading ? 'Sending...' : 'Send Reset Link' }}
            </button>

            <div class="text-center mt-4">
              <router-link
                to="/admin/login"
                class="text-sm text-slate-400 hover:text-white transition-colors"
              >
                Back to login
              </router-link>
            </div>
          </template>
      </div>
    </div>
  </div>
</template>
