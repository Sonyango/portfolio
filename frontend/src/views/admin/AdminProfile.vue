<script setup>
import { ref, onMounted, computed } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { useAuthStore } from '@/stores/authStore';
import {
  ShieldCheckIcon, DevicePhoneMobileIcon,
  EnvelopeIcon, KeyIcon, UserCircleIcon,
  EyeIcon, EyeSlashIcon, CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const { get, post, put }  = useApi()
const uiStore             = useUiStore()
const authStore           = useAuthStore()

// Profile data
const profile = ref(null)
const loading = ref(true)

async function loadProfile() {
  loading.value = true
  const { data } = await get('/admin/me')
  if (data) {
    profile.value   = data.user
    authStore.user  = data.user
  }
  loading.value = false
}

// Edit name
const editingName = ref(false)
const newName     = ref('')

async function saveName() {
  if (!newName.value.trim()) return
  const { data, success } = await put('/admin/profile', { name: newName.value.trim() })
  if (success) {
    profile.value.name  = newName.value.trim()
    authStore.user.name = newName.value.trim()
    editingName.value   = false
    uiStore.success('Name updated.')
  }
}

// Change password
const pwForm  = ref({ current: '', password: '', confirm: '' })
const showPw  = ref({ current: false, new: false, confirm: false })
const pwLoading = ref(false)

const pwStrength = computed(() => {
  const p = pwForm.value.password
  if (!p) return { score: 0, label: '', color: '' }
  let score = 0
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p))  score++
  if (/[a-z]/.test(p))  score++
  if (/\d/.test(p))     score++
  if (/[\W_]/.test(p))  score++
  const labels  = ['', 'Very weak', 'Weak', 'Fair', 'Strong', 'Very Strong']
  const colors  = ['', 'text-red-400', 'text-orange-400', 'text-amber-400', 'text-green-400', 'text-emerald-400']
  return { score, label: labels[score], color: colors[score] }
})

const pwValid = computed(() =>
  pwForm.value.password.length >= 12 &&
  /[A-Z]/.test(pwForm.value.password) &&
  /[a-z]/.test(pwForm.value.password) &&
  /\d/.test(pwForm.value.password) &&
  /[\W_]/.test(pwForm.value.password) &&
  pwForm.value.password === pwForm.value.confirm &&
  pwForm.value.current.length >= 12
)

async function changePassword() {
  if (!pwValid.value || pwLoading.value) return
  pwLoading.value = true
  const { success } = await post('/admin/change-password', {
    current_password:   pwForm.value.current,
    password:           pwForm.value.password,
    password_confirmation: pwForm.value.confirm,
  })
  if (success) {
    uiStore.success('Password changed successfully.')
    pwForm.value = { current: '', password: '', confirm: '' }
  }
  pwLoading.value = false
}

// MFA Management

// MFA Preference
const prefPassword  = ref('')
const savingPref    = ref(false)

async function setPreference(method) {
  if (!prefPassword.value) {
    uiStore.error('Enter your password to change this setting.')
    return
  }
  if (method === 'totp' && !profile.value.totp_enrolled) {
    uiStore.error('Enroll an authenticator app first.')
    return
  }
  savingPref.value = true
  const { success } = await put('/admin/mfa/preference', {
    method,
    password: prefPassword.value,
  })
  if (success) {
    profile.value.preferred_mfa_method = method
    authStore.user.preferred_mfa_method = method
    prefPassword.value = ''
    uiStore.success('Verification method updated.')
    await loadProfile()
  }
  savingPref.value = false
}

// TOTP Setup
const totpSetupStep = ref(null)
const totpSetupData = ref(null)
const totpCode      = ref('')
const setupPassword = ref('')
const recoveryCodes = ref([])
const totpLoading   = ref(false)

async function startTotpSetup() {
  totpLoading.value = true
  const { data, success } = await get('/admin/mfa/setup')
  if (success) {
    totpSetupData.value = data
    totpSetupStep.value = 'scan'
  }
  totpLoading.value = false
}

async function verifyAndEnableMfa() {
  if (!totpCode.value || totpCode.value.length !== 6 || !setupPassword.value) return
  totpLoading.value = true

  const { data, success } = await post('/admin/mfa/enable', {
    totp_code: totpCode.value,
    password:  setupPassword.value,
  })

  if (success) {
    recoveryCodes.value   = data.recovery_codes
    totpSetupStep.value   = 'recovery'
    profile.value.totp_enrolled   = true
    profile.value.mfa_enabled     = true
    profile.value.preferred_mfa_method  = 'totp'
    if (authStore.user) {
      authStore.user.totp_enrolled    = true
      authStore.user.mfa_enabled      = true
      authStore.user.preferred_mfa_method = 'totp'
    }
    uiStore.success('Authenticator enabled! TOTP is now your default method.')
  }
  totpLoading.value = false
}

function finishSetup() {
  totpSetupStep.value   = null
  totpSetupData.value   = null
  totpCode.value        = ''
  setupPassword.value   = ''
  recoveryCodes.value   = []
  loadProfile()
}

// TOTP Disable
const showDisableMfa  = ref(false)
const disablePassword = ref('')
const disableTotpCode = ref('')
const disableLoadng   = ref(false)

async function disableMfa() {
  if (!disablePassword.value || !disableTotpCode.value) return
  disableLoadng.value = true

  const { success } = await post('/admin/mfa/disable', {
    password: disablePassword.value,
    totp_code: disableTotpCode.value,
  })

  if (success) {
    uiStore.success('Authentcator removed. Email verification will be used.')
    showDisableMfa.value = false
    disablePassword.value = ''
    disableTotpCode.value = ''
    await loadProfile()
  }
  disableLoadng.value = false
}

onMounted(loadProfile)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Profile & Security" subtitle="Manage your account and verification settings" />

    <div v-if="loading" class="text-center py-20 text-slate-400">Loading...</div>

    <div v-else-if="profile" class="max-w-2xl space-y-6">

      <!-- Profile info -->
       <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-700">
          <UserCircleIcon class="w-5 h-5 text-ndigo-400" />
          <h3 class="text-white font-semibold">Account Information</h3>
        </div>

        <!-- Name -->
         <div class="mb-4">
          <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">
            Display Name
          </label>
          <div v-if="!editingName" class="flex items-center justify-between">
            <p class="text-white">{{ profile.name }}</p>
            <button @click="editingName = true; newName = profile.name"
              class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
              Edit
            </button>
          </div>
          <div v-else class="flex gap-2">
            <input v-model="newName" type="text" @keyup.enter="saveName"
              class="flex-1 bg-slate-900 border border-slate-600 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <button @click="saveName" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-xl transition-colors">Save</button>
            <button @click="editingName = false" class="px-4 py-2 border border-slate-600 text-slate-300 hover:bg-slate-700 text-sm rounded-xl transition-colors">Cancel</button>
          </div>
         </div>

         <!-- Email -->
          <div class="mb-4">
            <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Email</label>
            <p class="text-slate-300">{{ profile.email }}</p>
          </div>

          <!-- Stats -->
           <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-700">
            <div>
              <p class="text-xs text-slate-500 mb-1">Last Login</p>
              <p class="text-sm text-slate-300">{{ profile.last_login || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 mb-1">Password Changed</p>
              <p class="text-sm text-slate-300">{{ profile.password_changed_at || 'Never' }}</p>
            </div>
           </div>
       </div>

       <!-- 2FA Methods -->
        <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
          <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-700">
            <ShieldCheckIcon class="w-5 h-5 text-indigo-400" />
            <h3 class="text-white font-semibold">Two-Factor Authentication</h3>
          </div>

          <p class="text-slate-400 text-sm mb-5">
            Every login requres a second verification step.
            Choose your preferred method below.
          </p>

          <!-- Current preference -->
           <div class="mb-5 p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl">
             <p class="text-xs text-indigo-400 font-medium uppercase tracking-wider mb-1">
              Active Method
             </p>
             <p class="text-white text-sm font-medium">
              {{ profile.preferred_mfa_method === 'totp'
                  ? 'Authentcator App'
                  : 'Email verification Code' }}
             </p>
           </div>

           <!-- Email method card -->
            <div class="border border-slate-700 rounded-xl p-4 mb-3">
              <div class="flex items-start justfy-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-sky-500/10 flex items-center justify-center">
                    <EnvelopeIcon class="w-5 h-5 text-sky-400" />
                  </div>
                  <div>
                    <p class="text-white text-sm font-medium">Email Code</p>
                    <p class="text-slate-400 text-xs"> A 6-digit code will be sent to {{ profile.email }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 bg-green-500/10 text-green-400 text-xs rounded-full border border-green-500/20">
                    Always available
                  </span>
                  <span v-if="profile.preferred_mfa_method === 'email'"
                    class="px-2 py-0.5 bg-green-500/10 text-green-400 text-xs rounded-full border border-indigo-500/20">
                    Active
                  </span>
                </div>
              </div>
            </div>

            <!-- Authentcator app card -->
             <div class="border rounded-xl p-4 mb-5"
                :class="profile.totp_enrolled ? 'border-slate-700' : 'border-dashed border-slate-700'">
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                      <DevicePhoneMobileIcon class="w-5 h-5 text-indigo-400" />
                    </div>
                    <div>
                      <p class="text-white text-sm font-medium">Authenticator App</p>
                      <p class="text-slate-400 text-xs">
                        {{ profile.totp_enrolled
                            ? 'Enrolled - Google Authenticator or Authy'
                            : 'Not enrolled - click setup to enable' }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span v-if="profile.totp_enrolled"
                      class="flex items-center gap-1 px-2 py-0.5 bg-green-500/10 text-green-400 text-xs rounded-full border border-green-500/20">
                      <CheckCircleIcon class="w-3 h-3">Enrolled</CheckCircleIcon>
                    </span>
                    <span v-if="profile.preferred_mfa_method === 'totp'"
                      class="px-2 py-0.5 bg-green-500/10 text-green-400 text-xs rounded-full border border-indigo-500/20">
                      Active
                    </span>
                  </div>
                </div>

                <!-- Action buttons -->
                 <div class="mt-3 flex gap-2">
                  <button v-if="!profile.totp_enrolled" @click="startTotpSetup" :disabled="totpLoading"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
                    {{ totpLoading ? 'Loading...' : 'Setup authenticator' }}
                  </button>
                  <button v-else-if="profile.preferred_mfa_method !== 'totp'" @click="() => {}" class="hidden" />
                  <button v-if="profile.totp_enrolled" @click="showDisableMfa = !showDisableMfa"
                    class="px-4 py-2 border border-red-500/30 hover:bg-red-500/10 text-red-400 text-xs font-medium rounded-lg transition-colors">
                    Remove authenticator
                  </button>
                 </div>

                 <!-- Disable TOTP form -->
                  <div v-if="showDisableMfa"
                    class="mt-4 pt-4 border-t border-slate-700 space-y-3">
                    <div class="flex items-center gap-2 text-amber-400 text-xs mb-2">
                      <ExclamationTriangleIcon class="w-4 h-4 shrink-0" />
                        Removing the authenticator will switch back to email verification.
                    </div>
                    <input v-model="disablePassword" type="password" placeholder="Your current password"
                      class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    <input v-model="disableTotpCode" type="text" inputmode="numeric" placeholder="6-digit authenticator code" maxlength="6"
                      class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    <button @click="disableMfa" :disabled="disableLoadng || !disablePassword || !disableTotpCode"
                      class="w-full py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">
                      {{ disableLoadng ? 'Removing...' : 'Confirm Remove Authenticator' }}
                    </button>
                  </div>
              </div>

              <!-- Preference switcher -->
               <div v-if="profile.totp_enrolled" class="pt-4 border-t border-slate-700">
                <p class="text-sm font-medium text-slate-300 mb-3">
                  Switch to preferred method
                </p>
                <input v-model="prefPassword" type="password" placeholder="Enter your password to confirm change"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-3" />
                <div class="grid grid-cols-2 gap-3">
                  <button
                    @click="setPreference('email')" :disabled="savingPref || profile.preferred_mfa_method === 'email'"
                    :class="['py-2.5 rounded-xl text-sm font-medium transition-all border',
                      profile.preferred_mfa_method === 'email'
                        ? 'border-indigo-500 bg-indigo-500/10 text-indigo-400 cursor-default'
                        : 'border-slate-600 text-slate-300 hover:bg-slate-700 cursor-pointer']">
                    Use Email
                  </button>
                  <button
                    @click="setPreference('totp')" :disabled="savingPref || profile.preferred_mfa_method === 'totp'"
                    :class="['py-2.5 rounded-xl text-sm font-medium transition-all border',
                      profile.preferred_mfa_method === 'totp'
                        ? 'border-indigo-500 bg-indigo-500/10 text-indigo-400 cursor-default'
                        : 'border-slate-600 text-slate-300 hover:bg-slate-700 cursor-pointer']">
                    Use Authenticator
                  </button>
                </div>
               </div>
          </div>

          <!-- TOTP Setup modal -->
           <teleport to="body" v-if="totpSetupStep">
            <div class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center px-4">
              <div class="bg-slate-800 rounded-2xl border border-slate-700 w-full max-w-md p-8">

                <!-- Scan QR -->
                 <template v-if="totpSetupStep === 'scan'">
                  <h3 class="text-white font-semibold text-lg mb-2">Setup Authenticator</h3>
                  <p class="text-slate-400 text-sm mb-5">
                    Scan this QR code with your authenticator app
                    (Google Authenticator, Authy, 1Password, etc.)
                  </p>

                  <!-- QR Code via Google API -->
                   <div class="bg-white p-4 rounded-xl flex items-center justify-center mb-4">
                    <img
                      :src="`https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(totpSetupData.qr_code_url)}&size=200x200`"
                      alt="QR Code" class="w-48 h-48" />
                   </div>

                   <!-- Manual key -->
                    <div class="bg-slate-900 rounded-xl p-3 mb-5">
                      <p class="text-xs text-slate-500 mb-1">Manual entry key:</p>
                      <p class="text-indigo-300 font-mono text-sm break-all">
                        {{ totpSetupData.secret }}
                      </p>
                    </div>

                    <button @click="totpSetupStep = 'verify'"
                      class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                      I've scanned the code
                    </button>
                    <button @click="totpSetupStep = null"
                      class="w-full mt-2 py-2 text-sm text-slate-400 hover:text-white transition-colors">
                      Cancel
                    </button>
                 </template>

                 <!-- Verify code -->
                  <template v-else-if="totpSetupStep === 'verify'">
                    <h3 class="text-white font-semibold text-lg mb-2">Verify Setup</h3>
                    <p class="text-slate-400 text-sm mb-5">
                      Enter the 6-digit code from your authenticator app to confirm setup.
                    </p>

                    <div class="space-y-4 mb-5">
                      <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                          Current Password
                        </label>
                        <input v-model="setupPassword" type="password" placeholder="••••••••"
                          class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                          Authenticator Code
                        </label>
                        <input v-model="totpCode" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                          class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 text-white text-center text-2xl tracking-widest placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" />
                      </div>
                    </div>

                    <button @click="verifyAndEnableMfa"
                      :disabled="totpLoading || totpCode.length !== 6 || !setupPassword"
                      :class="['w-full py-3 rounded-xl font-medium transition-colors',
                        !totpLoading && totpCode.length === 6 && setupPassword
                          ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                          : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
                      {{ totpLoading ? 'Verifying...' : 'Activate Authenticator' }}
                    </button>
                    <button @click="totpSetupStep = 'scan'"
                      class="w-full mt-2 py-2 text-sm text-slate-400 hover:text-white transition-colors">
                      Back to QR Code
                    </button>
                  </template>

                  <!-- Recovery codes -->
                   <template v-else-if="totpSetupStep === 'recovery'">
                    <div class="text-center mb-5">
                      <CheckCircleIcon class="w-12 h-12 text-green-400 mx-auto mb-3" />
                      <h3 class="text-white font-semibold text-lg">Authenticator Enabled!</h3>
                      <p class="text-slate-400 text-sm mt-1">
                        Save these recovery codes in a safe place. The can only be shown once.
                      </p>
                    </div>

                    <div class="bg-slate-900 rounded-xl p-4 mb-5">
                      <div class="grid grid-cols-2 gap-2">
                        <div
                          v-for="code in recoveryCodes" :key="code"
                          class="font-mono text-sm text-indigo-300 bg-slate-800 rounded px-3 py-2 text-center">
                          {{ code }}
                        </div>
                      </div>
                    </div>

                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 mb-5">
                      <p class="text-amber-400 text-xs">
                        These codes are shown only once. Each code can be used once to recover access
                        if you lose your authentiicator. Store them securely.
                      </p>
                    </div>

                    <button @click="finishSetup"
                      class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl transition-colors">
                      I've saved my recovery codes
                    </button>
                   </template>
              </div>
            </div>
           </teleport>

        <!-- Change Password -->
         <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
          <div class="flex items-center gap-3 mb-5 pb-5 border-b border-slate-700">
            <KeyIcon class="w-5 h-5 text-indigo-400" />
            <h3 class="text-white font-semibold">Change Password</h3>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Current Password</label>
              <div class="relative">
                <input v-model="pwForm.current" :type="showPw.current ? 'text' : 'password'" placeholder="••••••••"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <button type="button" @click="showPw.current = !showPw.current"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                  <EyeSlashIcon v-if="showPw.current" class="w-5 h-5" />
                  <EyeIcon v-else class="w-5 h-5" />
                </button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">New Password</label>
              <div class="relative">
                <input v-model="pwForm.password" :type="showPw.new ? 'text' : 'password'" placeholder="Min 12 characters with uppercase, number, symbol"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
                <button type="button" @click="showPw.new = !showPw.new"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                <EyeSlashIcon v-if="showPw.new" class="w-5 h-5" />
                <EyeIcon v-else class="w-5 h-5" />
              </button>
              </div>
              <div v-if="pwForm.password" class="mt-2">
                <div class="flex gap-1 mb-1">
                  <div v-for="i in 5" :key="i"
                    :class="['h-1 flex-1 rounded-full transition-all',
                      i <= pwStrength.score
                        ? ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-green-500', 'bg-emerald-500'][i-1]
                        : 'bg-slate-700']" />
                </div>
                <p :class="['text-xs', pwStrength.color]">{{ pwStrength.label }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Confirm New Password</label>
              <div class="relative">
                <input v-model="pwForm.confirm" :type="showPw.confirm ? 'text' : 'password'"
                  placeholder="Repeat new password"
                  :class="['w-full bg-slate-900 border rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                    pwForm.confirm && pwForm.password !== pwForm.confirm ? 'border-red-500' : 'border-slate-600']" />
                <button type="button" @click="showPw.confirm = !showPw.confirm"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                  <EyeSlashIcon v-if="showPw.confirm" class="w-5 h-5" />
                  <EyeIcon v-else class="w-5 h-5" />
                </button>
              </div>
            </div>

            <button @click="changePassword" :disabled="!pwValid || pwLoading"
            :class="['w-full py-3 rounded-xl font-medium text-sm transition-colors',
              pwValid && !pwLoading
                ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
            {{ pwLoading ? 'Changing...' : 'Change Password' }}
          </button>
          </div>
         </div>
    </div>
  </AdminLayout>
</template>
