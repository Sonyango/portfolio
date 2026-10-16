<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import {
  EyeIcon, EyeSlashIcon, ShieldCheckIcon,
  LockClosedIcon, EnvelopeIcon, DevicePhoneMobileIcon,
  ChevronRightIcon, ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const route  = useRoute()
const router = useRouter();
const authStore = useAuthStore();

// Step tracking
// step: 'login' | 'method-select' | 'verify'
const step = ref('login')

// Login step state
const email = ref('');
const password = ref('');
const showPass = ref(false);

// MFA state
const mfaToken          = ref('')
const currentMethod     = ref('')
const preferredMethod   = ref('')
const availableMethods  = ref([])
const totpEnrolled      = ref(false)
const emailHint         = ref('')
const selectedMethod    = ref('')

// Verify step state
const code            = ref('');
const attemptsLeft    = ref(5)
const resending       = ref(false)
const resendCooldown  = ref(0)
const info            = ref('')

// Shared state
const error = ref('');
const warning = ref('');
const loading = ref(false);

const lockoutInfo = ref(null); // { seconds, type }
let countdownInterval = null
let resendInterval = null
let errorTimeout  = null

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

const canSubmitLogin = computed(() => !loading.value && emailValid.value && passwordValid.value)
const canSubmitVerify = computed(() => !loading.value && codeValid.value)
const canContinue     = computed(() => !!selectedMethod.value)

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



// Step 1: Submit password
async function handleLogin() {
  if (!canSubmitLogin.value) return

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
      mfaToken.value    = result.mfaToken
      currentMethod.value   = result.method
      emailHint.value   = result.emailHint || ''

      // Fetch method info for the selection screen
      await loadMethodInfo()

      // First login attempt (email only, no totp) go directly to verify step
      if (availableMethods.value.length === 1) {
        selectedMethod.value = 'email'
        step.value = 'verify'
        if (result.method === 'email') startResendCooldown(60)
      } else {
        selectedMethod.value = result.method
        step.value = 'method-select'
      }
      return
    }

    // Handle password-level errors
    handleLoginError(result)

  } catch {
    setError('An unexpected error occured. Please try again.')
  } finally {
    loading.value = false
  }
}

async function loadMethodInfo() {
  const result = await authStore.getMfaMethodInfo(mfaToken.value)
  if (result.success) {
    preferredMethod.value = result.data.preferred_method
    availableMethods.value = result.data.available_methods
    totpEnrolled.value = result.data.totp_enrolled
    emailHint.value = result.data.email_hint
  }
}

// Step 2: Method selection
async function handleContinueToVerify() {
  if (!canContinue.value) return
  error.value = ''
  loading.value = true

  try {
    if (selectedMethod.value !== currentMethod.value) {
      // Backend to switch the pending challenge
      const result = await authStore.switchMfaMethod(mfaToken.value, selectedMethod.value)

      if (!result.success) {
        if (result.expired) { goBackToLogin(); return }
        setError(result.message); loading.value = false; return
      }

      currentMethod.value = selectedMethod.value
      if (result.data?.email_hint) emailHint.value = result.data.email_hint
    }

    code.value = ''
    step.value = 'verify'
    attemptsLeft.value = 5

    if (currentMethod.value === 'email') startResendCooldown(60)

  } catch {
    setError('failed to continue. Please try again.')
  } finally { loading.value = false }
}

// Step 3: Code verification
async function handleVerify() {
  if (!canSubmitVerify.value) return

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
      setTimeout(goBackToLogin, 3000)
      return
    }

    attemptsLeft.value = result.attemptsLeft ?? (attemptsLeft.value - 1)

    if (result.status === 429 || attemptsLeft.value <= 0) {
      setError('Too many failed attempts. Please login again.')
      setTimeout(goBackToLogin, 3000)
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
        setTimeout(goBackToLogin, 3000)
      } else {
        setError(result.message)
      }
    }
  } finally {
    resending.value = false
  }
}

// Navigation helpers
function goBackToLogin() {
  step.value = 'login'
  mfaToken.value = ''
  error.value = ''
  warning.value = ''
  info.value = ''
  code.value = ''
  availableMethods.value = []; clearInterval(resendInterval)
  resendCooldown.value = 0; lockoutInfo.value = null
}

function goBackToMethodSelect() {
  step.value = 'method-select'; code.value = ''; error.value = ''
  clearInterval(resendInterval); resendCooldown.value = 0
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

  // Method labels
  const methodLabels = {
    email: { label: 'Email Code', desc: 'Receive a 6-digit code by email', icon: EnvelopeIcon },
    totp:  { label: 'Authenticator App', desc: 'Use Google Authenticator or Authy', icon: DevicePhoneMobileIcon },
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
          {{
            step === 'login'         ? 'Sign in to your account' :
            step === 'method-select' ? 'Choose verification method' :
                                       'Enter verification code'
          }}
        </p>
       </div>

       <!-- Progress steps -->
        <div class="flex items-center justify-center gap-2 mb-6">
          <div v-for="(s, i) in ['login', 'method-select', 'verify']" :key="s"
            class="flex items-center ga-2">
            <div
              :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                step === s
                  ? 'bg-indigo-600 text-white'
                  : (availableMethods.length <= 1 && s === 'method-select')
                    ? 'bg-slate-800 text-slate-700 scale-75'
                    : s === 'verify' && step === 'login'
                      ? 'bg-slate-700 text-slate-500'
                      : 'bg-slate-700 text-slate-500']"
              >
              {{ i + 1 }}
            </div>
            <div v-if="i < 2"
              class="w-6 h-px"
              :class="i === 0 && (step === 'method-select' || step === 'verify')
                ? 'bg-indigo-600' : 'bg-slate-700'"
            />
          </div>
        </div>

       <!-- Card -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 shadow-xl">

          <!-- Step 1: Lgin Form -->
           <template v-if="step === 'login'">
             <h2 class="text-xl font-semibold text-white mb-6">Sign in</h2>

             <!-- Lockout -->
             <div v-if="lockoutInfo" class="mb-4 py-3 rounded-xl bg-orange-500/10 border border-orange-500/30 text-sm">
              <p class="text-orange-400 font-medium">Account temporarily locked</p>
              <p class="text-orange-300/80 text-xs mt-1">
                Try again in <span class="font-mono font-bold">{{ formatCountdown(lockoutInfo.seconds) }}</span>
              </p>
             </div>

              <!-- Error -->
              <div v-if="error && !lockoutInfo" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                {{ error }}
              </div>

             <!-- Warning -->
             <div v-if="warning" class="mb-4 px-4 py-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm">
              {{ warning }}
             </div>

             <div class="space-y-4">
              <!-- Email -->
               <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                <input v-model="email" type="email" placeholder="admin@example.com"
                  autocomplete="email" @keyup.enter="handleLogin"
                  :class="['w-full bg-slate-900 border rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition-colors',
                    email && !emailValid ? 'border-red-500 focus:ring-red-500/30' : emailValid ? 'border-green-600 focus:ring-indigo-500' : 'border-slate-600 focus:ring-indigo-500'
                  ]" />
                  <p v-if="email && !emailValid" class="mt-1 text-xs text-red-400">Please enter a valid email address</p>
               </div>

               <!-- Password -->
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">Password</label>
                  <div class="relative">
                    <input v-model="password" :type="showPass ? 'text' : 'password'"
                      placeholder="••••••••" autocomplete="current-password" @keyup.enter="handleLogin"
                      :class="['w-full bg-slate-900 border rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition-colors',
                        password && !passwordValid ? 'border-red-500 focus:ring-red-500/30' : passwordValid ? 'border-green-600 focus:ring-indigo-500' : 'border-slate-600 focus:ring-indigo-500'
                      ]" />
                    <button type="button" @click="showPass = !showPass"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                      <EyeSlashIcon v-if="showPass" class="w-5 h-5" />
                      <EyeIcon v-else class="w-5 h-5" />
                    </button>
                </div>
                <p v-if="password && !passwordValid" class="mt-1 text-xs text-red-400">Password must be at least 12 characters</p>
                </div>

                <!-- Forgot password -->
                 <div class="text-right">
                  <router-link to="/admin/forgot-password"
                    class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                    Forgot password?
                  </router-link>
                 </div>

                 <!-- Form submision -->
                  <button @click="handleLogin" :disabled="!canSubmitLogin || !!lockoutInfo"
                    :class="['w-full py-3 rounded-xl font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2',
                      canSubmitLogin && !lockoutInfo
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg' : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
                      <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                      <span>{{ loading ? 'Signing in...' : lockoutInfo ? 'Account Locked' : 'Sign in' }}</span>
                      <ChevronRightIcon v-if="!loading && !lockoutInfo" class="w-4 h-4" />
                  </button>
             </div>

             <!-- Security hint -->
              <div class="mt-5 p-3 bg-slate-900/50 rounded-xl border border-slate-700">
                <p class="text-xs text-slate-400 text-center">
                  <ShieldCheckIcon class="w-3.5 h-3.5 inline mr-1 text-indigo-400" />
                  Verification required after providing password.
                </p>
              </div>
           </template>

          <!-- Step 2: MFA Method Selection -->
            <template v-else-if="step === 'method-select'">

              <div class="flex items-center gap-3 mb-6">
                <button @click="goBackToLogin" class="p-1 text-slate-400 hover:text-white transition-colors">
                  <ArrowLeftIcon class="w-5 h-5" />
                </button>
                <h2 class="text-lg font-semibold text-white">Verification Method</h2>
              </div>

              <p class="text-slate-400 text-sm mb-5">
                Choose how you'd like to verify your identity. Your preferred method is highlighted.
              </p>

              <!-- Error -->
               <div v-if="error" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                {{ error }}
               </div>

               <!-- Method options -->
                <div class="space-y-3 mb-6">
                  <button
                    v-for="method in availableMethods"
                    :key="method"
                    @click="selectedMethod = method"
                    :class="['w-full flex items-center gap-4 px-4 py-4 rounded-xl border transition-all text-left',
                      selectedMethod === method
                        ? 'border-indigo-500 bg-indigo-500/10'
                        : 'border-slate-700 hover:border-slate-500 bg-slate-900/50']"
                  >
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0',
                      selectedMethod === method ? 'bg-indigo-500/20' : 'bg-slate-800']">
                      <component
                        :is="methodLabels[method].icon"
                        :class="['w-5 h-5', selectedMethod === method ? 'text-indigo-400' : 'text-slate-400']"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2">
                        <p class="text-sm font-medium" :class="selectedMethod === method ? 'text-white' : 'text-slate-300'">
                          {{ methodLabels[method].label }}
                        </p>
                        <span v-if="method === preferredMethod"
                          class="px-1.5 py-0.5 bg-indigo-500/10 text-indigo-400 text-xs rounded-full border border-indigo-500/20">
                          Preferred
                        </span>
                      </div>
                      <p class="text-xs text-slate-500 mt-0.5">
                        {{ method === 'email'
                            ? 'Code sent to ' + emailHint
                            : methodLabels[method].desc }}
                      </p>
                    </div>
                    <div :class="['w-5 h-5 rounded-full border-2 shrink-0 flex items-center justify-center',
                      selectedMethod === method ? 'border-indigo-500 bg-indigo-500' : 'border-slate-600']">
                      <div v-if="selectedMethod === method" class="w-2 h-2 rounded-full bg-white" />
                    </div>
                  </button>
                </div>

                <!-- Continue button -->
                 <button @click="handleContinueToVerify" :disabled="!canContinue || loading"
                    :class="['w-full py-3 rounded-xl font-medium text-sm transition-all flex items-center justify-center gap-2',
                      canContinue && !loading
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg' : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
                    <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    <span>{{ loading ? 'Setting up...' : 'Continue to Verification' }}</span>
                    <ChevronRightIcon v-if="!loading" class="w-4 h-4" />
                  </button>
            </template>

          <!-- Step 3: Verify code -->
           <template v-else-if="step === 'verify'">

            <div class="flex items-center gap-3 mb-6">
              <button
                v-if="availableMethods.length > 1"
                @click="goBackToMethodSelect"
                class="p-1 text-slate-400 hover:text-white transition-colors">
                <ArrowLeftIcon class="w-5 h-5" />
              </button>
              <button
                v-else
                @click="goBackToLogin"
                class="p-1 text-slate-400 hover:text-white transition-colors">
                <ArrowLeftIcon class="w-5 h-5" />
              </button>
              <h2 class="text-lg font-semibold text-white">
                {{ currentMethod === 'totp' ? 'Authenticator Code' : 'Email Verification' }}
              </h2>
            </div>

            <!-- Method icon + instruction -->
             <div class="text-center mb-6">
              <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
                :class="currentMethod === 'totp' ? 'bg-indigo-500/10' : 'bg-sky-500/10'">
                <DevicePhoneMobileIcon v-if="currentMethod === 'totp'" class="w-7 h-7 text-indigo-400" />
                <EnvelopeIcon v-else class="w-7 h-7 text-sky-400" />
              </div>
              <p class="text-slate-400 text-sm leading-relaxed">
                <template v-if="currentMethod === 'totp'">
                  Open your authentcator app and enter the 6-digit code.
                </template>
                <template v-else>
                  A code has been sent to
                  <span class="text-sky-400 font-medium">{{ emailHint }}</span>.
                  Check your inbox and spam folder.
                </template>
              </p>
             </div>

             <!-- Info (resend success) -->
              <div v-if="info" class="mb-4 px-4 py-3 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 text-sm text-center">
                {{ info }}
              </div>

              <!-- Error -->
               <div v-if="error" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                {{ error }}
               </div>

               <!-- Attempts indicator -->
                <div v-if="attemptsLeft < 5 && attemptsLeft > 0 && !error"
                  class="mb-4 px-3 py-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs text-center">
                  {{ attemptsLeft }} attempt(s) remaining
                </div>

                <!-- Code input -->
                 <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1">
                    {{ currentMethod === 'totp' ? 'Authenticator Code' : 'Email Code' }}
                  </label>
                  <input v-model="code" type="text" inputmode="numeric" pattern="[0-9]*"
                    maxlength="7" placeholder="0 0 0 0 0 0" @keyup.enter="handleVerify"
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-4
                          text-white text-center text-3xl tracking-[0.4em] placeholder-slate-700
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" />
                 </div>

                 <!-- Verify button -->
                  <button @click="handleVerify" :disabled="!canSubmitVerify"
                    :class="['w-full py-3 rounded-xl font-medium text-sm transition-all flex items-center justify-center gap-2',
                      canSubmitVerify
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg' : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
                      <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                      <span>{{ loading ? 'Verifying...' : 'Verify Code' }}</span>
                  </button>

                  <!-- Resend (email only) -->
                   <div v-if="currentMethod === 'email'" class="mt-4 text-center">
                    <p class="text-slate-500 text-xs mb-2">Didn't receive the code?</p>
                    <button @click="handleResend" :disabled="resendCooldown > 0 || resending" class="text-sm transition-colors"
                      :class="resendCooldown > 0 || resending ? 'text-slate-600 cursor-not-allowed' : 'text-indigo-400 hover:text-indigo-300'">
                      <span v-if="resending">Sending...</span>
                      <span v-else-if="resendCooldown > 0">Resend in {{ resendCooldown }}s</span>
                      <span v-else>Resend code</span>
                    </button>
                   </div>
           </template>
        </div>
    </div>
  </div>
</template>
