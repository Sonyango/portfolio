<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import {
  EyeIcon,
  EyeSlashIcon,
  ShieldCheckIcon,
  LockClosedIcon,
  EnvelopeIcon,
  DevicePhoneMobileIcon,
} from '@heroicons/vue/24/outline';

const route  = useRoute()
const router = useRouter();
const authStore = useAuthStore();

// Form state
const email = ref('');
const password = ref('');
const code = ref('');
const showPass = ref(false);
const error = ref('');
const warning = ref('');
const loading = ref(false);
const info  = ref('')

// MFA state
const mfaRequired = ref(false);
const mfaToken = ref('');
const mfaMethod = ref('')  // totp or email
const emailHint = ref('')
const attemptsLeft = ref(5)
const resending = ref(false)
const resendCooldown = ref(0)

// Lockout state
const lockoutInfo = ref(null); // { seconds, type }
let countdownInterval = null
let resendInterval = null
let errorTimeout  = null
//let messageTimeout = null

// Validation
const emailValid = computed(() =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())
)

const passwordValid = computed(() =>
  password.value.length >=8
)

const codeValid = computed(() =>
  code.value.replace(/\s/g, '').length >= 6
)

const canSubmit = computed(() => {
  if (loading.value) return false
  if (mfaRequired.value) return code.value.length === 6
  return emailValid.value && passwordValid.value
})

// Clear error after 60s
function setError(msg, seconds = 60) {
  error.value = msg
  clearTimeout(errorTimeout)
  if (msg) {
    errorTimeout = setTimeout(() => {
      error.value = ''
    }, seconds * 1000)
  }
}

// Only clear error when email becomes valid
watch(emailValid, (isValid) => {
  if (isValid && error.value) {
    clearTimeout(errorTimeout)
    error.value = ''
  }
})

// Lockout countdown
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

// Resend cooldown
function startResendCooldown(seconds = 60) {
  resendCooldown.value = seconds
  clearInterval(resendInterval)
  resendInterval = setInterval(() => {
    if (resendCooldown.value <= 1) {
      resendCooldown.value = 0
      clearInterval(resendInterval)
    } else {
      resendCooldown.value--
    }
  }, 1000)
}

onMounted(() => {
  const reason = route.query.reason
  if (reason === 'inactivity') {
    setError('Session expired due to inactivity. Please login again.', 120)
  } else if (reason === 'unauthorized') {
    setError('Your session is no longer valid. Please login again.', 120)
  }
})

// Cleanup on unmount
onUnmounted(() => {
  clearInterval(countdownInterval)
  clearInterval(resendInterval)
  clearTimeout(errorTimeout)
})

// function clearMessagesAfterDelay(seconds = 10) {
//   clearTimeout(messageTimeout)

//   messageTimeout = setTimeout(() => {
//     error.value = ''
//     warning.value = ''
//   }, seconds * 1000)
// }



// Step 1: Submit password
async function handleLogin() {
  if (!canSubmit.value) return

  error.value = ''
  warning.value = ''
  loading.value = true

  try {
    const result = await authStore.login(email.value.trim(), password.value)

    if (result.success) {
      router.push({ name: 'admin.dashboard' })
      return
    }

    if (result.mfaRequired) {
      mfaRequired.value = true
      mfaToken.value    = result.mfaToken
      mfaMethod.value   = result.method
      emailHint.value   = result.emailHint || ''
      attemptsLeft.value = 5
      code.value        = ''

      if (result.method === 'email') {
        // Start resend cooldown so user waits before resending
        startResendCooldown(60)
      }
      return
    }

    // Handle password-level errors
    handleLoginError(result)

  } catch {
    setError('An unexpected error occured. Please try again.')
    //error.value = 'An unexpected error occured. Please try again.'
    //clearMessagesAfterDelay(60)
  } finally {
    loading.value = false
  }
}

// Step 2: Submit MFA code
async function handleMfaVerify() {
  if (!canSubmit.value) return

  error.value = ''
  loading.value = true

  try {
    const cleanCode = code.value.replace(/\s/g, '')
    const result = await authStore.verifyMfa(mfaToken.value, cleanCode)

    if (result.success) {
      router.push({ name: 'admin.dashboard' })
      return
    }

    if (result.expired) {
      setError('Session expired. Please login again.')
      setTimeout(resetToLogin, 3000)
      return
    }

    attemptsLeft.value = result.attemptsLeft ?? (attemptsLeft.value - 1)

    if (result.status === 429 || attemptsLeft.value <= 0) {
      setError('Too many failed attempts. Please login again.')
      setTimeout(resetToLogin, 3000)
      return
    }

    setError(
      attemptsLeft.value > 0
        ? `Invalid code. ${attemptsLeft.value} attempt(s) remaining.`
        : 'Invalid code.'
    )
    code.value = ''

  } catch {
    setError('Verification failed. Please try again.')
    //error.value = 'Verification failed. Please try again.'
  } finally {
    loading.value = false
  }
}

// Resend email code
async function handleResend() {
  if (resendCooldown.value > 0 || resending.value) return

  resending.value = true
  error.value     = ''

  try {
    const result = await authStore.resendEmailCode(mfaToken.value)

    if (result.success) {
      info.value = result.message
      code.value = ''
      attemptsLeft.value = 5
      startResendCooldown(60)
      setTimeout(() => info.value = '', 5000)
    } else {
      if (result.expired) {
        setError('Session expired. Please login again.')
        setTimeout(resetToLogin, 3000)
      } else {
        setError(result.message)
      }
    }
  } finally {
    resending.value = false
  }
}

function handleLoginError(result) {
    const status = result.status

    if (status === 429) {
      if (result.lockoutType === 'ip') {
        //error.value = result.message || 'Too many attempts. Try agin later.'
        setError(result.message || 'Too many attempts. Try again later.')
      } else {
        startCountdown(result.retryAfter || 300)
        //error.value = result.message || 'Too many attempts.'
        setError(result.message || 'Too many attempts.')
      }
      return
    }

    if (status === 423) {
      startCountdown(result.retryAfter || 300)
      //error.value = result.message || 'Account temporarily locked.'
      setError(result.message || 'Account temporarily locked.')
      return
    }

    //error.value = result.message || 'Invalid email or password.'
    setError(result.message || 'Invalid email or password.')
    warning.value = result.warning || ''
    //clearMessagesAfterDelay(60)
  }

  // Reset to login step
function resetToLogin() {
    mfaRequired.value = false
    mfaToken.value    = ''
    mfaMethod.value   = ''
    emailHint         = ''
    code.value        = ''
    error.value       = ''
    info.value        = ''
    attemptsLeft.value = 5
    clearInterval(resendInterval)
    resendCooldown.value = 0
  }

  // watch(emailValid, (isValid) => {
  //   if (isValid && error.value) {
  //     clearTimeout(messageTimeout)
  //     error.value = ''
  //   }
  // })
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
          {{ !mfaRequired
              ? 'Sign in to your account'
              : mfaMethod === 'totp'
                ? 'Authenticator app verification'
                : 'Email verification' }}
        </p>
       </div>

       <!-- Card -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 shadow-xl">


          <!-- MFA Verification view -->
           <template v-if="mfaRequired">

            <div class="text-center mb-6">
              <div class="w-14 h-14 rounded-full flex items-center
                          justify-center mx-auto mb-3"
                :class="mfaMethod === 'totp'
                  ? 'bg-indigo-500/10'
                  : 'bg-sky-500/10'"
              >
                <DevicePhoneMobileIcon
                  v-if="mfaMethod === 'totp'"
                  class="w-6 h-6 text-indigo-400" />
                <EnvelopeIcon v-else
                  class="w-6 h-6 text-sky-400" />
              </div>

              <h2 class="text-white font-semibold text-lg">
                {{ mfaMethod === 'totp'
                    ? 'Authenticator Code'
                    : 'Check Your Email' }}
              </h2>

              <p class="text-slate-400 text-sm mt-1 leading-relaxed">
                <template v-if="mfaMethod === 'totp'">
                  Open your authenticator app and enter the
                  6-digit code shown for this account.
                </template>
                <template v-else>
                  We sent a 6-digit code to
                  <span class="text-sky-400 font-medium">{{ emailHint }}</span>
                  Check your inbox and spam folder.
                </template>
              </p>
            </div>

            <!-- Info message (resend success)-->
             <div v-if="info"
                class="mb-4 px-4 py-3 rounded-xl bg-sky-500/10 border
                    border-sky-500/20 text-sky-400 text-sm text-center">
                {{ info }}
              </div>

             <!-- Error -->
            <div v-if="error"
                class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ error }}
            </div>

            <!-- Attempts remaining indicator -->
             <div v-if="attemptsLeft < 5 && attemptsLeft > 0 && !error "
                class="mb-4 px-3 py-2 rounded-lg bg-amber-500/10
                      border border-amber-500/20 text-amber-400 text-xs text-center">
                {{ attemptsLeft }} attempt(s) remaining.
            </div>

            <!-- Code input -->
             <div class="mb-5">
              <label class="block text-sm font-medium mb-1"
                :class="mfaMethod === 'totp' ? 'text-slate-300' : 'text-slate-300'">
                {{ mfaMethod === 'totp'
                    ? '6-Digit Authenticator Code'
                    : '6-Digit Email Code' }}
              </label>
              <input
                v-model="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                placeholder="0 0 0 0 0 0"
                @keyup.enter="handleMfaVerify"
                class="w-full bg-slate-900 border border-slate-600 rounded-xl
                      px-4 py-4 text-white text-center text-2xl tracking-[0.4em]
                      placeholder-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                />
             </div>

             <button
                @click="handleMfaVerify"
                :disabled="!canSubmit"
                :class="['w-full py-3 rounded-xl font-medium text-sm transition-all',
                  canSubmit
                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg'
                    : 'bg-slate-700 text-slate-500 cursor-not-allowed']"
             >
              {{ loading ? 'Verifying...' : 'Verify Code' }}
             </button>

             <!-- Resend button, email method only-->
              <div v-if="mfaMethod === 'email'" class="mt-4 text-center">
                <p class="text-slate-500 text-xs mb-2">
                  Didn't receive the code?
                </p>
                <button
                  @click="handleResend"
                  :disabled="resendCooldown > 0 || resending"
                  class="text-sm transition-colors"
                  :class="resendCooldown > 0 || resending
                    ? 'text-slate-600 cursor-not-allowed'
                    : 'text-indigo-400 hover:text-indigo-300'"
                >
                  <span v-if="resending">Sending...</span>
                  <span v-else-if="resendCooldown > 0">
                    Resend in {{ resendCooldown }}s
                  </span>
                  <span v-else> Resend code</span>
                </button>
              </div>

              <!-- Back to login -->
             <button
                @click="resetToLogin"
                class="w-full mt-3 py-2 text-sm text-slate-400
                            hover:text-white transition-colors"
             >
              Back to login
             </button>

           </template>

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
                  <span class="font-mono font-bold text-orange-300">
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
                                  'text-white placeholder-slate-500 transition-colors',
                                  'focus:outline-none focus:ring-2',
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
                   <div class="mt-5 p-3 bg-slate-900/50 rounded-xl border border-slate-700">
                    <p class="text-xs text-slate-400 text-center">
                      <ShieldCheckIcon class="w-4 h-4 inline mr-1 text-indigo-400" />
                      Verifcation required after password authentication.
                    </p>
                   </div>

             </template>

        </div>
    </div>
  </div>
</template>
