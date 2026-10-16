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

</script>
