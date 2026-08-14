<script setup>
import { ref, watch, computed } from 'vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { useSlug } from '@/composables/useSlug';
import { XMarkIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  project: { type: Object, default: null }
})

const emit = defineEmits(['saved', 'close'])

const { post: apiPost, put, loading, errors } = useApi()
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
const fileInputRef      = ref(null)

//Track original for dirty check
const originalForm    = ref(null)
const originalPreview = ref(null)

// Category options
const categoryOptions = [
  { value: '',        label: 'Select category' },
  { value: 'fullstack', label: 'Full Stack' },
  { value: 'frontend', label: 'Frontend' },
  { value: 'api',      label: 'API / Backend' },
  { value: 'ict',      label: 'ICT / Systems' },
  { value: 'mobile',   label: 'Mobile' },
]

// Form validation
const isFormValid = computed(() => {
  return form.value.title.trim().length > 0 &&
         form.value.slug.trim().length > 0 &&
         form.value.description.trim().length > 0
})

const missingFields = computed(() => {
  const missing = []
  if (!form.value.title.trim())   missing.push('Title')
  if (!form.value.slug.trim())    missing.push('Slug')
  if (!form.value.description.trim()) missing.push('Description')
  return missing
})

// Dirty check for edit mode
const isFormDirty = computed(() => {
  if (!isEdit.value)  return true
  if (!originalForm.value) return false
  const current = JSON.stringify({
    ...form.value,
    tech_stack: [...form.value.tech_stack].sort()
  })
  const original = JSON.stringify({
    ...originalForm.value,
    tech_stack: [...(originalForm.value.tech_stack || [])].sort()
  })
  const previewChanged = thumbnailPreview.value !== originalPreview.value
  return current !== original || previewChanged
})

const canSubmit = computed(() => {
  return isFormValid.value &&
        (!isEdit.value || isFormDirty.value) &&
        !loading.value
})

// Populate form when editing
watch(() => props.project, (project) => {
  if (project) {
    // Populate all fields from the selected project
    form.value = {
      title:        project.title       || '',
      slug:         project.slug        || '',
      description:  project.description || '',
      content:      project.content     || '',
      tech_stack:   Array.isArray(project.tech_stack)
                      ? [...project.tech_stack]
                      : [],
      live_url:     project.live_url    || '',
      github_url:   project.github_url  || '',
      category:     project.category    || '',
      featured:     project.featured    ?? false,
      published:    project.published   ?? true,
      order:        project.order       || 0,
    }
    thumbnailPreview.value = project.thumbnail  || null
    //thumbnailFile   = null

    // Save originals for dirty tracking
    originalForm.value    = JSON.parse(JSON.stringify(form.value))
    originalPreview.value = thumbnailPreview.value
  } else{
    // Reset for new project
    form.value = {
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
    }
    thumbnailPreview.value  = null
    thumbnailFile.value     = null
    originalForm.value      = null
    originalPreview.value   = null
  }
}, { immediate: true })

// Auto-generate slug from title (new projects only)
watch(() => form.value.title, (title) => {
  if (!isEdit.value) {
    form.value.slug = generateSlug(title)
  }
})

// Tech stack tag handling
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

function onTechKeydown(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    addTech()
  }
  // Remove last tag on backspace if input is empty
  if (e.key === 'Backspace' && !techInput.value && form.value.tech_stack.length) {
    form.value.tech_stack.pop()
  }
}

// Thumbnail handling
function triggerFileInput() {
  fileInputRef.value?.click()
}

function onThumbnailChange(e) {
  const file = e.target.files[0]
  if (!file) return

  if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type)) {
    uiStore.error('Please upload a JPG, PNG or WEBP image.')
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    uiStore.error('Image must be smaller than 2MB.')
    return
  }

  thumbnailFile.value = file
  thumbnailPreview.value = URL.createObjectURL(file)
}

function removeThumbnail() {
  thumbnailFile.value     = null
  thumbnailPreview.value  = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

// Project form submission
async function handleSubmit() {
  if (!canSubmit.value) return

  // FormData to handle file upload correctly
  const formData = new FormData()

  formData.append('title',        form.value.title)
  formData.append('slug',         form.value.slug)
  formData.append('description',  form.value.description)
  formData.append('published',    form.value.published ? '1' : '0')
  formData.append('featured',     form.value.featured ? '1' : '0')
  formData.append('order',        form.value.order)

  if (form.value.content) {
    formData.append('content', form.value.content)
  }

  if (form.value.category) {
    formData.append('category', form.value.category)
  }

  if (form.value.live_url) {
    formData.append('live_url', form.value.live_url)
  }

  if (form.value.github_url) {
    formData.append('github_url', form.value.github_url)
  }

  // Append tech stack as array
  form.value.tech_stack.forEach(tech => {
    formData.append('tech_stack[]', tech)
  })

  // Append thumbnail only if a new file selected
  if (thumbnailFile.value instanceof File) {
    formData.append('thumbnail', thumbnailFile.value, thumbnailFile.value.name)
  }

  let result

  if (isEdit.value) {
    // Laravel PUT doesn't support multipart, use POST with _method spoofing
    formData.append('_method', 'PUT')
    result = await apiPost(`/admin/projects/${props.project.id}`, formData)
  } else {
    result = await apiPost('/admin/projects', formData)
  }

  if (result.success) {
    uiStore.success(
      isEdit.value
        ? 'Project updated successfully.'
        : 'Project created successfully.')
    emit('saved')
  }
}
</script>

<template>
  <!-- Drawer overlay -->
   <div class="fixed inset-0 bg-black/60 z-40" />

   <!-- Drawer panel -->
    <div class="fixed right-0 top-0 h-full w-full max-w-2xl bg-slate-800
                border border-slate-700 z-50 overflow-y-auto">

      <!-- Header -->
       <div class="flex items-center justify-between px-6 py-4 border-b
                  border-slate-700 sticky top-0 bg-slate-800 z-10">
        <h3 class="text-lg font-semibold text-white">
          {{ isEdit ? 'Edit Project' : 'Add Project' }}
        </h3>
        <button @click="emit('close')"
          class="p-2 text-slate-400 hover:text-white rounded-lg transition-colors">
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
            label="Slug"
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
            <!-- Tags display -->
            <div v-if="form.tech_stack.length > 0"
              class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="tag in form.tech_stack"
                :key="tag"
                class="flex items-center gap-1.5 px-3 py-1 bg-indigo-500/10 text-indigo-300 rounded-full text-sm border border-indigo-500/30">
                {{ tag }}
                <button
                  @click="removeTech(tag)"
                  class="hover:text-white transition-colors text-base leading-none">x</button>
              </span>
            </div>
            <!-- Tag input -->
            <div class="flex gap-2">
              <input
                v-model="techInput"
                @keydown="onTechKeydown"
                placeholder="e.g. Laravel, Vue.js - press Enter to add"
                class="flex-1 bg-slate-900 border border-slate-600
                      rounded-xl px-4 py-2.5 text-white placeholder-slate-500
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"/>
                <button
                  @click="addTech"
                  type="button"
                  class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white
                        rounded-xl text-sm transition-colors">
                  Add
                </button>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              Press Enter or click Add afetr each technology.
            </p>
           </div>
           <!-- Live URL -->
           <FormInput
            label="Live URL"
            v-model="form.live_url"
            type="url"
            placeholder="https://myproject.com"
            :error="errors.live_url?.[0]"
           />
           <!-- GitHub URL -->
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
              <!-- Preview with remove button -->
              <div v-if="thumbnailPreview" class="mb-3 relative">
                <img
                  :src="thumbnailPreview"
                  alt="Preview"
                  class="w-full h-40 object-cover rounded-xl border border-slate-700" />
                  <button
                    @click="removeThumbnail"
                    class="absolute top-2 right-2 p-1.5 bg-red-600 hover:bg-red-700
                          text-white rounded-lg transition-colors"
                    title="Remove thumbnail">
                    <TrashIcon class="w-4 h-4" />
                  </button>
              </div>

              <!-- Upload zone -->
               <div
                  v-if="!thumbnailPreview"
                  @click="triggerFileInput"
                  class="flex flex-col items-center justify-center gap-2 w-full h-32
                        border-2 border-dashed border-slate-600 rounded-xl text-slate-400
                        hover:border-indigo-500 hover:text-indigo-400 cursor-pointer transition-colors">
                  <PhotoIcon class="w-8 h-8" />
                  <span class="text-sm font-medium">Click to upload thumbnail</span>
                  <span class="text-xs text-slate-500">JPG, PNG or WEBP, max 2MB</span>
                </div>
                <!-- Replace button -->
                 <button
                    v-if="thumbnailPreview"
                    @click="triggerFileInput"
                    type="button"
                    class="mt-2 flex items-center gap-2 px-2 py-2 border border-slate-600
                          hover:border-slate-500 text-slate-300 hover:text-white text-sm
                          font-medium rounded-xl transition-colors">
                    <PhotoIcon class="w-4 h-4" />
                    Replace thumbnail
                  </button>

                  <!-- Hidden file input -->
                   <input
                      ref="fileInputRef"
                      type="file"
                      accept="image/jpeg,image/jpg,image/png,image/webp"
                      class="hidden"
                      @change="onThumbnailChange"
                    />
            </div>

            <!-- Toggles -->
             <div class="flex gap-6 pt-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.published"
                  class="w-4 h-4 rounded accent-indigo-600"
                />
                  <span class="text-sm text-slate-300">Published</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.featured"
                  class="w-4 h-4 rounded accent-indigo-600"
                />
                  <span class="text-sm text-slate-300">featured</span>
              </label>
             </div>
             <!-- Order -->
             <FormInput
              label="Order"
              v-model="form.order"
              type="number"
              placeholder="0"
             />

             <!-- Validation hint -->
              <div
                v-if="!isFormValid && missingFields.length > 0"
                class="px-4 py-3 bg-amber-500/10 border border-amaber-500/20
                      rounded-xl text-amber-400 text-sm">
                <p class="font-medium mb-1">Required before saving:</p>
                <ul class="text-xs space-y-0.5 list-disc list-inside">
                  <li v-for="field in missingFields" :key="field">
                    {{ field }} is required
                  </li>
                </ul>
              </div>
        </div>

        <!-- Footer -->
         <div class="sticky bottom-0 px-6 py-4 border-t border-slate-700
                    bg-slate-800 flex gap-3 justify-end">
          <button
           @click="emit('close')"
           class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
               hover:bg-slate-700 text-sm font-medium transition-colors">
            Cancel
          </button>

          <button
            @click="handleSubmit"
            :disabled="!canSubmit"
            :title="!isFormValid
              ? 'Fill in: ' + missingFields.join(', ')
              : isEdit && !isFormDirty
                ? 'Make a change before updating'
                : ''"
            :class="['px-6 py-2.5 rounded-xl text-sm font-medium transition-all',
              canSubmit
                ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shadow-lg'
                : 'bg-slate-600 text-slate-400 cursor-not-allowed opacity-60']">
               {{ loading ? 'Saving...' : (isEdit ? 'Update Project' : 'Create Project') }}
          </button>
         </div>
    </div>
</template>
