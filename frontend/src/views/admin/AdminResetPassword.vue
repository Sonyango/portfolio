<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api  from '@/api/index.js';
import { LockClosedIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const route   = useRoute()
const router  = useRouter()

const password        = ref('')
const confirmPassword = ref('')
const showPass        = ref(false)
const showConfirm     = ref(false)
const loading         = ref(false)
const success         = ref(false)
const error           = ref('')
const token           = ref('')
const email           = ref('')

onMounted(() => {
  token.value = route.query.token || ''
  email.value = route.query.email || ''
  if (!token.value || !email.value) {
    error.value = 'Invalid reset link. Please request a new one.'
  }
})

const passwordStrength = computed(() => {
  const p = password.value
  if (!p) return { score: 0, label: '', color: '' }

  let score = 0
  if (p.length >= 12)   score++
  if (/[A-Z]/.test(p))  score++
  if (/[a-z]/.test(p))  score++
  if (/\d/.test(p))     score++
  if (/[\W_]/.test(p))  score++

  const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong']
  const colors = ['', 'text-red-400', 'text-orange-400',
                  'text-amber-400', 'text-green-400', 'text-emerald-400']

  return { score, label: labels[score], color: colors[score] }
})

const isValid = computed(() => {
  return password.value.length >= 8 &&
          /[A-Z]/.test(password.value) &&
          /[a-z]/.test(password.value) &&
          /\d/.test(password.value) &&
          /[\W_]/.test(password.value) &&
          password.value === confirmPassword.value
})

async function handleReset() {
  if (!isValid.value || loading.value) return
  loading.value = true
  error.value   = ''

  try {
    await api.post('/admin/reset-password', {
      email:            email.value,
      token:            token.value,
      password:         password.value,
      password_confirm: confirmPassword.value
    })

    success.value = true
    setTimeout(() => router.push('/admin/login'), 3000)

  } catch (err) {
    error.value = err.response?.data?.message
      || 'Reset failed. The link may have expired.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4">
    <div class="w-full max-w-md">

      <div class="text-center mb-8">
        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <LockClosedIcon class="w-6 h-6 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-white">Set New Password</h1>
      </div>

      <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">

        <!-- Success -->
         <div v-if="success" class="text-center">
          <span class="text-4xl">✅</span>
          <h3 class="text-white font-semibold mt-3 mb-2">Password Reset!</h3>
          <p class="text-slate-400 text-sm">
            Redirecting to login page...
          </p>
         </div>

         <!-- Invalid link error -->
          <div v-else-if="error && !token"
            class="text-center">
            <p class="text-red-400 mb-4">{{ error }}</p>
            <router-link to="/admin/forgot-password"
              class="text-indigo-400 hover:text-ndigo-300 text-sm">
              Request new reset link
            </router-link>
          </div>

          <!-- Form -->
           <template v-else>
            <div v-if="error"
              class="mb-4 px-4 py-3 rounded-xl bg-red-500/10
                    border border-red-500/30 text-red-400 text-sm">
              {{ error }}
            </div>

            <div class="space-y-4">

              <!-- New Password -->
               <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">
                  New Password
                </label>
                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPass ? 'text' : 'password'"
                    placeholder="Min 8 characters with uppercase, number, symbol"
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl
                            px-4 py-3 pr-12 text-white placeholder-slate-500
                            focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                  />
                  <button type="button" @click="showPass = !showPass"
                    class="absolute right-3 top-1/2 -translate-y-1/2
                          text-slate-400 hover:text-white">
                    <EyeSlashIcon v-if="showPass" class="w-5 h-5" />
                    <EyeIcon v-else class="w-5 h-5" />
                  </button>
                </div>

                <!-- Strength indicator -->
                 <div v-if="password" class="mt-2">
                  <div class="flex gap-1 mb-1">
                    <div v-for="i in 5" :key="i"
                      :class="['h-1 flex-1 rounded-full transition-all',
                        i <= passwordStrength.score
                          ? ['bg-red-500', 'bg-orange-500', 'bg-amber-500',
                            'bg-green-500', 'bg-emerald-500'][i-1]
                          : 'bg-slate-700']"
                    />
                  </div>
                  <p :class="['text-xs', passwordStrength.color]">
                    {{ passwordStrength.label }}
                  </p>
                 </div>

                 <!-- Requirements -->
                  <ul class="mt-2 space-y-1">
                    <li v-for="req in [
                      { label: 'At least 8 characters', ok: password.length >= 8 },
                      { label: 'Uppercase letter',      ok: /[A-Z]/.test(password) },
                      { label: 'Lowercase letter',      ok: /[a-z]/.test(password) },
                      { label: 'Number',                ok: /\d/.test(password) },
                      { label: 'Special character',     ok: /[\W_]/.test(password) },
                    ]" :key="req.label"
                      :class="['text-xs flex items-center gap-1',
                               req.ok ? 'text-green-400' : 'text-slate-500']">
                      <span>{{ req.ok ? '✓' : '○' }}</span>
                      {{ req.label }}
                    </li>
                  </ul>
               </div>

               <!-- Confirm password -->
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">
                    Confirm Password
                  </label>
                  <div class="relative">
                    <input
                      v-model="confirmPassword"
                      :type="showConfirm ? 'text' : 'password'"
                      placeholder="Repeat your new password"
                      class="w-full bg-slate-900 border rounded-xl px-4 py-3 pr-12
                            text-white placeholder-slate-500 focus:outline-none
                            focus:ring-2 text-sm transition-colors"
                      :class="confirmPassword && password !== confirmPassword
                        ? 'border-red-500 focus:ring-red-500/30'
                        : confirmPassword && password === confirmPassword
                          ? 'border-green-600 focus:ring-indigo-500'
                          : 'border-slate-600 focus:ring-indigo-500'" />

                    <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2
                              text-slate-400 hover:text-white">
                        <EyeSlashIcon v-if="showConfirm" class="w-5 h-5" />
                        <EyeIcon  v-else class="w-5 h-5" />
                    </button>
                  </div>
                  <p v-if="confirmPassword && password !== confirmPassword"
                    class="mt-1 text-xs text-red-400">
                    Passwords do not match.
                  </p>
                  <p v-if="confirmPassword && password === confirmPassword && password"
                    class="mt-1 text-xs text-green-400">
                    ✓ Passwords match
                  </p>
                </div>

                <button
                  @click="handleReset"
                  :disabled="!isValid || loading"
                  :class="['w-full py-3 rounded-xl font-medium text-sm transition-all',
                    isValid && !loading
                      ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                      : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
                  {{ loading ? 'Resetting...' : 'Reset Password' }}
                </button>
                
            </div>
           </template>
      </div>
    </div>
  </div>
</template>
