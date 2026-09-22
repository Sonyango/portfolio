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
async function handleMfaVerify() {
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
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">

      <!-- Logo / title -->
       <div class="text-center mb-8">
        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <LockClosedIcon class="w-6 h-6 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-white font-display">Portfolio Admin</h1>
        <p class="text-slate-400 mt-1 text-sm">
          {{ mfaRequired
              ? 'Two-factor authentication required'
              : 'Sign in to your account' }}
        </p>
       </div>

       <!-- Card -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 shadow-xl">


          <!-- MFA Verification view -->
           <template v-if="mfaRequired">
            <div class="text-center mb-6">
              <div class="w-12 h-12 bg-indigo-500/10 rounded-full flex items-center
                          justify-center mx-auto mb-3">
                <ShieldCheckIcon class="w-6 h-6 text-indigo-400" />
              </div>
              <h2 class="text-white font-semibold">Verify Your Identity</h2>
              <p class="text-slate-400 text-sm mt-1">
                Open your authenticator app and enter the 6-digit code.
              </p>
            </div>

             <!-- Error -->
            <div v-if="error"
                class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ error }}
            </div>

            <!-- TOTP input -->
             <div class="mb-5">
              <label class="block text-sm font-medium text-slate-300 mb-1">
                Authenticator <Code></Code>
              </label>
              <input
                v-model="totpCode"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                placeholder="000000"
                @keyup.enter="handleMfaVerify"
                class="w-full bg-slate-900 border border-slate-600 rounded-xl
                      px-4 py-3 text-white text-center text-2xl tracking-widest
                      placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
             </div>

             <button
                @click="handleMfaVerify"
                :disabled="!canSubmit"
                :class="['w-full py-3 rounded-xl font-medium text-sm transitionall',
                  canSubmit
                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                    : 'bg-slate-700 text-slate-500 cursor-not-allowed']"
             >
              {{ loading ? 'Verifying...' : 'Verify Code' }}
             </button>

             <button
                @click="resetToLogin"
                class="w-full mt-3 py-2 text-sm text-slate-400
                            hover:text-white transition-colors"
             >
              Back to login
             </button>

           </template>



           <!-- Form -->
            <!-- <div class="space-y-4">
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
            </div> -->

            <!-- Login Form View -->
             <template v-else>
              <h2 class="text-xl font-semibold text-white mb-6">Sign in</h2>

              <!-- Lockout countdown -->
               <div v-if="lockoutInfo"
                  class="mb-4 px-4 py-3 rounded-xl bg-orange-500/10
                        border border-orange-500/30 text-sm">
                <p class="text-orange-400 font-medium">Account temporarily locked</p>
                <p class="text-orange-300/80 text-xs mt-1">
                  Try again in
                  <span class="font-mono font-bold">
                    {{ formatCountdown(lockoutInfo.seconds) }}
                  </span>
                </p>
               </div>

                <!-- Error -->
                <div v-if="error && !lockoutInfo"
                    class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                  {{ error }}
                </div>

                <!-- Warning (attempts remaining) -->
                 <div v-if="warning"
                    class="mb-4 px-4 py-3 rounded-xl bg-amber-500/10
                          border border-amber-500/30 text-amber-400 text-sm">
                    {{ warning }}
                  </div>

                  <div class="space-y-4">
                    <!-- Email -->
                     <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                          Email
                        </label>
                        <input
                          v-model="email"
                          type="email"
                          placeholder="admin@portfolio.test"
                          autocomplete="email"
                          @keyup.enter="handleLogin"
                          :class="['w-full bg-slate-900 border rounded-xl px-4 py-3',
                                  'text-white placeholder-slate-500',
                                  'focus:outline-none focus:ring-2 transition-colors',
                                  email && !emailValid
                                    ? 'border-red-500 focus:ring-red-500/30'
                                    : emailValid
                                      ? 'border-green-600 focus:ring-indigo-500'
                                      : 'border-slate-600 focus:ring-indigo-500']"
                          />
                          <p v-if="email && !emailValid"
                            class="mt-1 text-xs text-red-400">
                            Please enter a valid email address
                          </p>
                     </div>

                     <!-- Password -->
                      <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                          Password
                        </label>
                        <div class="relative">
                          <input
                            v-model="password"
                            :type="showPass ? 'text' : 'password'"
                            placeholder="........"
                            autocomplete="current-password"
                            @keyup.enter="handleLogin"
                            :class="['w-full bg-slate-900 border rounded-xl px-4 py-3 pr-12',
                                      'text-white placeholder-slate-500',
                                      'focus:outline-none focus:ring-2 transition-colors',
                                      password && !passwordValid
                                        ? 'border-red-500 focus:ring-red-500/30'
                                        : passwordValid
                                          ? 'border-green-600 focus:ring-indigo-500'
                                          : 'border-slate-600 focus:ring-indigo-500']"
                            />
                            <!-- Show/hide toggle -->
                             <button
                                type="button"
                                @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                      text-slate-400 hover:text-white transition-colors"
                             >
                             <EyeSlashIcon v-if="showPass" class="w-5 h-5" />
                             <EyeIcon v-else class="w-5 h-5" />
                            </button>
                        </div>
                        <p v-if="password && !passwordValid"
                          class="mt-1 text-xs text-red-400">
                          Password must be at least 8 characters
                        </p>
                      </div>

                      <!-- Forgot password link -->
                       <div class="text-right">
                        <router-link
                          to="/admin/forgot-password"
                          class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                        >
                          Forgot password?
                        </router-link>
                       </div>

                       <!-- Sign in button disabled until valid -->
                        <button
                          @click="handleLogin"
                          :disabled="!canSubmit || !!lockoutInfo"
                          :class="['w-full py-3 rounded-xl font-medium text-sm',
                                    'transition-all duration-200',
                            canSubmit && !lockoutInfo
                              ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg'
                              : 'bg-slate-700 text-slate-500 cursor-not-allowed']"
                        >
                        <span v-if="loading">Signing in...</span>
                        <span v-else-if="lockoutInfo">Account Locked</span>
                        <span v-else>Sign in</span>
                      </button>

                  </div>

                  <!-- Security badge -->
                   <div class="mt-6 flex items-center justify-center gap-2
                              text-xs text-slate-500">
                     <ShieldCheckIcon class="w-4 h-4 text-indigo-500" />
                     <span>Protected with rate limiting and account lockout.</span>
                   </div>

             </template>

        </div>
    </div>
  </div>
</template>
