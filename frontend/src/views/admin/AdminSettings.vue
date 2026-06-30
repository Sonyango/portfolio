<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';

const { get, put, loading } = useApi()
const uiStore = useUiStore()

const form = ref({
  site_name:      '',
  site_tagline:   '',
  hero_title:     '',
  hero_subtitle:  '',
  about_text:     '',
  github_url:     '',
  linkedin_url:   '',
  twitter_url:    '',
  email:          '',
  phone:          '',
  location:       '',
  cv_url:         '',
  available_for_work: 'true',
})

async function fetchSettings() {
  const { data } = await get('/admin/settings')
  if (data?.data) {
    Object.keys(form.value).forEach(key => {
      if (data.data[key] !== undefined) {
        form.value[key] = data.data[key]
      }
    })
  }
}

async function handleSave() {
  const settings = Object.entries(form.value).map(([key, value]) => ({
    key,
    value: String(value),
    group: getGroup(key),
  }))

  const { success } = await put('/admin/settings', { settings })
  if (success) {
    uiStore.success('Settings saved successfully.')
  }
}

function getGroup(key) {
  if (['site_name', 'site_tagline'].includes(key)) return 'general'
  if (['hero_title', 'hero_subtitle'].includes(key)) return 'hero'
  if (['github_url', 'linkedin_url', 'twitter_url'].includes(key)) return 'social'
  if (['email', 'phone', 'location'].includes(key)) return 'contact'
  return 'general'
}

onMounted(fetchSettings)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Settings" subtitle="Manage your site-wide content and preferences" />

    <div class="max-w-3xl space-y-8">

      <!--General-->
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-4 pb-3 border-b border-slate-700">
          General
        </h3>
        <div class="space-y-4">
          <FormInput label="Site Name" v-model="form.site_name"
            placeholder="My Portfolio" />
          <FormInput label="Site Tagline" v-model="form.site_tagline"
            placeholder="Full Stack Developer & ICT Professional" />
        </div>
      </div>

      <!--Hero section-->
      <div class="bg-slate-800 rounded-2xl border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-4 pb-3 border-b border-slate-700">
          Hero Section
        </h3>
        <div class="space-y-4">
          <FormInput label="Hero Title" v-model="form.hero_title"
            placeholder="Hi, I'm [Your Name]" />
          <FormInput label="Hero Subtitle" v-model="form.hero_subtitle"
            placeholder="I build modern web applications..." :rows="2" />
            <div class="flex items-center gap-2">
              <input type="checkbox"
                :checked="form.available_for_work === 'true'"
                @change="form.available_for_work = $event.target.checked ? 'true' : 'false'"
                id="available" class="w-4 h-4 accent-indigo-600" />
              <label for="available" class="text-sm text-slate-300">
                Show "Available for work" badge
              </label>
            </div>
        </div>
      </div>

      <!--About-->
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-4 pb-3 border-b border-slate-700">
          About Me
        </h3>
        <FormTextarea label="About Text" v-model="form.about_text"
          placeholder="Tell visitors about yourself..." :rows="5" />
      </div>

      <!--Contact info-->
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-4 pb-3 border-b border-slate-700">
          Contact Information
        </h3>
        <div class="space-y-4">
          <FormInput label="Email" v-model="form.email"
            type="email" placeholder="you@example.com" />
          <FormInput label="Phone"  v-model="form.phone"
            placeholder="+254 700 000 000" />
          <FormInput label="Location" v-model="form.location"
            placeholder="Nairobi, Kenya" />
          <FormInput label="CV / Resume URL" v-model="form.cv_url"
            placeholder="https://..." />
        </div>
      </div>

      <!--Social links-->
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-4 pb-3 border-b border-slate-700">
          Social links
        </h3>
        <div class="space-y-4">
          <FormInput label="GitHub" v-model="form.github_url"
            type="url" placeholder="https://github.com/username" />
          <FormInput label="LinkedIn" v-model="form.linkedin_url"
            type="url" placeholder="https://linkedin.com/in/username" />
          <FormInput label="Twitter / X" v-model="form.twitter_url"
            type="url" placeholder="https://twitter.com/username" />
        </div>
      </div>

      <!--Save button-->
      <div class="flex justify-end pb-8">
        <button
          @click="handleSave"
          :disabled="loading"
          class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50
                 text-white font-medium rounded-xl transition-colors">
          {{ loading ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>
