<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import {
  EyeIcon,
  EyeSlashIcon,
  ShieldCheckIcon,
  LockClosedIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();

// Form state
const email = ref('');
const password = ref('');
const totpCode = ref('');
const showPass = ref(false);
const error = ref('');
const warning = ref('');
const mfaRequired = ref(false);
const mfaToken = ref('');
const loading = ref(false);
const lockoutInfo = ref(null); // { seconds, type }

// Validation
const emailValid = computed(() =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())
)

const passwordValid = computed(() =>
  password.value.length >=8
)

const canSubmit = computed(() => {
  if (loading.value) return false
  if (mfaRequired.value) return totpCode.value.length === 6
  return emailValid.value && passwordValid.value
})

// Lockout countdown
let countdownInterval = null

function startCountdown(seconds) {
  lockoutInfo.value = { seconds, type: 'account' }
  clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (lockoutInfo.value.seconds <= 1) {
      lockoutInfo.value = null
      clearInterval(countdownInterval)
    } else {
      lockoutInfo.value.seconds--
    }
  }, 1000)
}

function formatCountdown(seconds) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return m > 0
    ? `${m}m ${s.toString().padStart(2, '0')}s`
    : `${s}s`
}

// Clear error when user types
watch([email, password], () => {
  error.value = ''
  warning.value = ''
})

// Handle login
async function handleLogin() {
  // error.value = '';
  // const result = await authStore.login(email.value, password.value);
  // if (result.success) {
  //   router.push({ name: 'admin.dashboard' })
  // } else {
  //   error.value = result.message
  // }

  if (!canSubmit.value) return

  error.value = ''
  warning.value = ''
  loading.value = true

  try {
    const result = await authStore.login(email.value, password.value)

    if (result.success) {
      router.push({ name: 'admin.dashboard' })
      return
    }

    if (result.mfaRequired) {
      mfaRequired.value = true
      mfaToken.value    = result.mfaToken
      loading.value     = false
      return
    }

    // Handle specific error types
    handleLoginError(result)

  } catch {
    error.value = 'An unexpected error occured. Please try again.'
  } finally {
    loading.value = false
  }
}

// MFA verifcation
async function handleMfaVerification() {
  if (!canSubmit.value) return

  error.value = ''
  loading.value = true

  try {
    const result = await authStore.verifyMfa(mfaToken.value, totpCode.value)

    if (result.success) {
      router.push({ name: 'admin.dashboard' })
      return
    }

    error.value = result.message || 'Invalid code. Try again.'
  } catch {
    error.value = 'Verification failed. Please try again.'
  } finally {
    loading.value = false
  }

  function handleLoginError(result) {
    const status = result.status

    if (status === 429) {
      if (result.lockoutType === 'ip') {
        error.value = result.message || 'Too many attempts. Try agin later.'
      } else {
        startCountdown(result.retryAfter || 300)
        error.value = result.message || 'Too many attempts.'
      }
      return
    }

    if (status === 423) {
      startCountdown(result.retryAfter || 300)
      error.value = result.message || 'Account temporarily locked.'
      return
    }

    error.value = result.message || 'Invalid email or password.'
    warning.value = result.warning || ''
  }

  function resetToLogin() {
    mfaRequired.value = false
    mfaToken.value    = ''
    totpCode.value    = ''
    error.value       = ''
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4">
    <div class="w-full max-w-md">

      <!-- Logo / title -->
       <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white font-display">Portfolio</h1>
        <p class="text-slate-400 mt-1">Admin Dashboard</p>
       </div>

       <!-- Card -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">
          <h2 class="text-xl font-semibold text-white mb-6">Sign in</h2>

          <!-- Error -->
           <div v-if="error"
              class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            {{ error }}
           </div>

           <!-- Form -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                <input
                  v-model="email"
                  type="email"
                  placeholder="admin@portfolio.test"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5
                        text-white placeholder-slate-500 focus:outline-none
                        focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Password</label>
                <input
                  v-model="password"
                  type="password"
                  placeholder="••••••••"
                  @keyup.enter="handleLogin"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5
                        text-white placeholder-slate-500 focus:outline-none
                        focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <button
                @click="handleLogin"
                :disabled="authStore.loading"
                class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50
                      text-white font-medium py-2.5 rounded-xl transition-colors mt-2">
                {{ authStore.loading ? 'Signing in...' : 'Sign in' }}
              </button>
            </div>
        </div>
    </div>
  </div>
</template>
