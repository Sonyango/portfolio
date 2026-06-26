<script setup>
import { ref, watch, computed } from 'vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { useSlug } from '@/composables/useSlug';
import { XMarkIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  project: { type: Object, default: null }
})

const emit = defineEmits(['saved', 'close'])

const { post, put, loading, errors } = useApi()
const uiStore = useUiStore()
const { generateSlug } = useSlug()

const isEdit = computed(() => !!props.project)

const form = ref({
  title:        '',
  slug:         '',
  description:  '',
  content:      '',
  tech_stack:   [],
  live_url:     '',
  github_url:   '',
  category:     '',
  featured:     false,
  published:    true,
  order:        0,
})

const techInput         = ref('')
const thumbnailFile     = ref(null)
const thumbnailPreview  = ref(null)

const categoryOptions = [
  { value: '',        label: 'Select category' },
  { value: 'fullstack', label: 'Full Stack' },
  { value: 'frontend', label: 'Frontend' },
  { value: 'api',      label: 'API / Backend' },
  { value: 'ict',      label: 'ICT / Systems' },
  { value: 'mobile',   label: 'Mobile' },
]

// Populate form when editing
watch(() => props.project, (project) => {
  if (project) {
    form.value = {
      title:        project.title       || '',
      slug:         project.slug        || '',
      description:  project.description || '',
      content:      project.content     || '',
      tech_stack:   project.tech_stack  || [],
      live_url:     project.live_url    || '',
      github_url:   project.github_url  || '',
      category:     project.category    || '',
      featured:     project.featured    || false,
      published:    project.published   ?? true,
      order:        project.order       || 0,
    }
    thumbnailPreview.value = project.thumbnail  || null
  }
}, { immediate: true })

// Auto-generate slug from title
watch(() => form.value.title, (title) => {
  if (!isEdit.value) {
    form.value.slug = generateSlug(title)
  }
})

function addTech() {
  const tag = techInput.value.trim()
  if (tag && !form.value.tech_stack.includes(tag)) {
    form.value.tech_stack.push(tag)
  }
  techInput.value = ''
}

function removeTech(tag) {
  form.value.tech_stack = form.value.tech_stack.filter(t => t !== tag)
}

function onThumbnailChange(e) {
  const file = e.target.files[0]
  if (!file) return
  thumbnailFile.value = file
  thumbnailPreview.value = URL.createObjectURL(file)
}

async function handleSubmit() {
  const formData = new FormData()

  Object.entries(form.value).forEach(([key, value]) => {
    if (key === 'tech_stack') {
      value.forEach(t => formData.append('tech_stack[]', t))
    } else {
      formData.append(key, value === true ? 1 : value === false ? 0 : value)
    }
  })

  if (thumbnailFile.value) {
    formData.append('_method', 'PUT')
    result = await post(`/admin/projects/${props.project.id}`, formData)
  } else {
    result = await post('/admin/projects', formData)
  }

  if (result.success) {
    uiStore.success(isEdit.value ? 'Project updated.' : 'Project created.')
    emit('saved')
  }
}
</script>

<template>
  <!-- Drawer overlay -->
   <div class="fixed inset-0 bg-black/60 z-40" @click="emit('close')" />

   <!-- Drawer panel -->
    <div class="fixed right-0 top-0 h-full w-full max-w 2xl bg-slate-800 border border-slate-700 z-50 overflow-y-auto">

      <!-- Header -->
       <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700 sticky top-0 bg-slate-800">
        <h3 class="text-lg font-semibold text-white">
          {{ isEdit ? 'Edit Project' : 'Add Project' }}
        </h3>
        <button @click="emit('close')"
          class="p-2 text-slate-400 hover:text-white rounded-lg">
          <XMarkIcon class="w-5 h-5" />
        </button>
       </div>

       <!-- Form Body -->
        <div class="p-6 space-y-5">

          <FormInput
            label="Title"
            v-model="form.title"
            placeholder="Project"
            :required="true"
            :error="errors.title?.[0]"
          />

          <FormInput
            label="slug"
            v-model="form.slug"
            placeholder="project"
            :error="errors.slug?.[0]"
          />

          <FormTextarea
            label="Short Description"
            v-model="form.description"
            placeholder="A brief summary of the project..."
            :rows="3"
            :required="true"
            :error="errors.description?.[0]"
          />

          <FormSelect
            label="Category"
            v-model="form.category"
            :options="categoryOptions"
          />

          <!-- Tech stack tags-->
           <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">
              Tech Stack
            </label>
            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="tag in form.tech_stack"
                :key="tag"
                class="flex items-center gap-1 5 px-3 py-1 bg-indigo-500/10 text-indigo-300 rounded-full text-sm border border-indigo-500/10">
                {{ tag }}
                <button @click="removeTech(tag)" class="hover:text-white">x</button>
              </span>
            </div>
            <div class="flex gap-2">
              <input
                v-model="techInput"
                @keydown.enter.prevent="addTech"
                placeholder="e.g. Laravel, Vue.js"
                class="flex-1 bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"/>
                <button class="px-4 py-2 5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-sm transition-colors">
                  Add
                </button>
            </div>
           </div>

           <FormInput
            label="Live URL"
            v-model="form.live_url"
            type="url"
            placeholder="https://myproject.com"
            :error="errors.live_url?.[0]"
           />

           <FormInput
            label="GitHub URL"
            v-model="form.github_url"
            type="url"
            placeholder="https://github.com/username/repo"
            :error="errors.github_url?.[0]"
           />

           <!-- Thumbnail upload-->
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">
                Thumbnail
              </label>
              <div v-if="thumbnailPreview" class="mb-3">
                <img
                  :src="thumbnailPreview" alt="Preview"
                  class="w-full h-40 object-cover rounded-xl border border-slate-700" />
              </div>
              <label class="flex items-center justify-center gap-2 w-full h-24 border-2 border-dashed border-slate-600 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-400 cursor-pointer transition-colors">
                <PhotoIcon class="w-5 h-5" />
                <span class="text-sm">Click to upload thumbnail</span>
                <input type="file" accept="image/*" class="hidden"
                  @change="onThumbnailChange" />
              </label>
            </div>

            <!-- Toggles -->
             <div class="flex gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.published"
                  class="w-4 h-4 rounded accent-indigo-600" />
                  <span class="text-sm text-slate-300">Published</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.featured"
                  class="w-4 h-4 rounded accent-indigo-600" />
                  <span class="text-sm text-slate-300">featured</span>
              </label>
             </div>

             <FormInput
             label="Order"
             v-model="form.order"
             type="number"
             placeholder="0"
             />
        </div>

        <!-- Footer -->
         <div class="sticky bottom-0 px-6 py-4 border-t border-slate-700 bg-slate-800 flex gap-3 justify-end">
          <button
           @click="emit('close')"
           class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
               hover:bg-slate-700 text-sm font-medium transition-colors">
            Cancel
          </button>
          <button
            @click="handleSubmit"
            :disabled="loading"
            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700
               disabled:opacity-50 text-white text-sm font-medium
               transition-colors">
               {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
          </button>
         </div>
    </div>
</template>
