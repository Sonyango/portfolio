<script setup>
import { ref, watch, computed } from 'vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import TiptapEditor from '@/components/admin/TiptapEditor.vue';
import DateTimePicker from '@/components/admin/DateTimePicker.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { useSlug } from '@/composables/useSlug';
import { XMarkIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ post: { type: Object, default: null } })
const emit  = defineEmits(['saved', 'close'])

const { post: apiPost, loading, errors } = useApi()
const uiStore = useUiStore()
const { generateSlug } = useSlug()
const isEdit  = computed(() => !!props.post)

const form = ref({
  title:        '',
  slug:         '',
  content:      '',
  excerpt:      '',
  status:       'draft',
  published_at: '',
})

const thumbnailFile     = ref(null)
const thumbnailPreview  = ref(null)
const fileInputRef      = ref(null)

const statusOptions = [
  { value:  'draft',      label: 'Draft' },
  { value:  'published',  label:  'Published' },
  { value:  'scheduled',  label:  'Scheduled' },
]

const originalForm = ref(null)
const originalPreview = ref(null)

// Form validation
// Publish button is disabled until all required fields are filled
const isFormValid = computed(() => {
  const titleOk   = form.value.title.trim().length > 0
  const slugOk    = form.value.slug.trim().length > 0
  const contentOk = form.value.content.trim().length > 0

  // If scheduled, published_at is also required
  if (form.value.status === 'scheduled') {
    return titleOk && slugOk && contentOk && !!form.value.published_at
  }
  return titleOk && slugOk && contentOk
})

const missingFields = computed(() => {
  const missing = []
  if (!form.value.title.trim()) missing.push('Title')
  if (!form.value.slug.trim())  missing.push('Slug')
  if (!form.value.content.trim()) missing.push('Content')
  if (form.value.status === 'scheduled' && !form.value.published_at) {
    missing.push('Publish date & time')
  }
  return missing
})

// Convert published_at to datetime-local format

function toDatetimeLocalFormat(dateStr) {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    // Format: YYYY-MM-DDTHH:MM
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
    return date.toISOString().slice(0, 16)
  } catch {
    return ''
  }
}

// Form changes tracker
const isFormDirty = computed(() => {
  if (!isEdit.value) return true
  if (!originalForm.value) return false

  const current = JSON.stringify(form.value)
  const original = JSON.stringify(originalForm.value)
  const previewChanged = thumbnailPreview.value !== originalPreview.value

  return current !== original || previewChanged
})

// Populate form when editing
watch(()  => props.post, (p) => {
  if (p) {
    form.value  = {
      title:        p.title         || '',
      slug:         p.slug          || '',
      content:      p.content       || '',
      excerpt:      p.excerpt       || '',
      status:       p.status        || 'draft',
      published_at: p.published_at_raw
                      ? toDatetimeLocalFormat(p.published_at_raw)
                      : toDatetimeLocalFormat(p.published_at) || '',
    }
    thumbnailPreview.value  = p.thumbnail  || null

    originalForm.value = JSON.parse(JSON.stringify(form.value))
    originalPreview.value = thumbnailPreview.value
  } else {
    // Reset for new post
    form.value = {
      title:        '',
      slug:         '',
      content:      '',
      excerpt:      '',
      status:       'draft',
      published_at: '',
    }
    thumbnailPreview.value = null
    thumbnailFile.value   = null

    originalForm.value = null
    originalPreview.value = null
  }
}, { immediate: true })

// Auto-generate slug from title (new posts only)
watch(() => form.value.title, (title) => {
  if (!isEdit.value) form.value.slug = generateSlug(title)
})

// Clear published_at when status changes away from scheduled
watch(() => form.value.status, (newStatus) => {
  if (newStatus !== 'scheduled') {
    form.value.published_at = ''
  }
})

// Thumbnail handling
function triggerFileInput() {
  fileInputRef.value?.click()
}

function onThumbnailChange(e) {
  const file = e.target.files[0]
  if (!file) return

  // Validate type
  if (!['image/jpeg', 'image/png', 'image/webp', 'image/jpg'].includes(file.type)) {
    uiStore.error('Please upload a JPG, PNG or WEBP image.')
    return
  }

  // Validate size (2MB)
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
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

// Submiting the form
async function handleSubmit() {
  if (!isFormValid.value) return

  const formData = new FormData()

  // Append text fields
  formData.append('title',    form.value.title)
  formData.append('slug',     form.value.slug)
  formData.append('content',  form.value.content)
  formData.append('status',   form.value.status)

  if (form.value.excerpt) {
    formData.append('excerpt', form.value.excerpt)
  }

  if (form.value.published_at && form.value.status === 'scheduled') {
    formData.append('published_at', form.value.published_at)
  }

  if (thumbnailFile.value instanceof File) {
    formData.append('thumbnail', thumbnailFile.value, thumbnailFile.value.name)
  }

  let result
  if (isEdit.value) {
    // Laravel doesn't support PUT with multipart - us POST plus _method spoofing
    formData.append('_method', 'PUT')
    result = await apiPost(`/admin/posts/${props.post.id}`, formData)
  } else {
    result = await apiPost('/admin/posts', formData)
  }

  if (result.success) {
    uiStore.success(
      isEdit.value
        ? 'Post updated successfully.'
        : form.value.status === 'scheduled'
          ? 'Post scheduled successfully.'
          : 'Post published successfully.'
    )
    emit('saved')
  }
}
</script>

<template>
  <!-- Overlay -->
  <div class="fixed inset-0 bg-black/60 z-40" />

  <!-- Drawer -->
  <div class="fixed right-0 top-0 h-full w-full max-w-3xl bg-slate-800
              border-l border-slate-700 z-50 overflow-y-auto">

  <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4
                border-b border-slate-700 sticky top-0 bg-slate-800 z-10">
          <div>
            <h3 class="text-lg font-semibold text-white">
            {{ isEdit ? 'Edit Post' : 'New Post' }}
          </h3>
          <p v-if="form.status === 'scheduled' && form.published_at"
            class="text-xs text-indigo-400 mt-0.5">
            Scheduled post will go live automatically
          </p>
          </div>
          <button @click="emit('close')"
            class="p-2 text-slate-400 hover:text-white rounded-lg transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
      </div>

      <!-- Form body -->
      <div class="p-6 space-y-5">

        <!-- Title -->
        <FormInput
          label="Title" v-model="form.title"
          placeholder="Post title" :required="true"
          :error="errors.title?.[0]"
        />

        <!-- Slug -->
        <FormInput
          label="Slug" v-model="form.slug"
          placeholder="post-slug"
          :error="errors.slug?.[0]"
        />

        <!-- Content (WYSIWYG)-->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">
            Content <span class="text-red-400">*</span>
          </label>
          <TiptapEditor v-model="form.content" />
          <p v-if="errors.content?.[0]" class="mt-1 text-xs text-red-400">
            {{ errors.content[0] }}
          </p>
          <!-- Character count hin -->
           <p class="mt-1 text-xs text-slate-500 text-rght">
            {{ form.content.replace(/<[^>]*>/g, '').length }} characters
           </p>
        </div>

        <!-- Excerpt -->
        <FormTextarea
          label="Excerpt" v-model="form.excerpt"
          placeholder="Short summary shown in blog listing (optional)..."
          :rows="3"
          :error="errors.excerpt?.[0]"
        />

        <!-- Status & scheduled date -->
        <!-- <div class="grid grid-cols-2 gap-4"> -->
          <FormSelect
            label="Status"  v-model="form.status"
            :options="statusOptions"
          />

          <!-- Scheduled DateTime picker, only shown for scheduled posts -->
           <transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2">
              <div v-if="form.status === 'scheduled'"
                class="bg-slate-900/50 border border-indigo-500/20 rounded-xl p-4">
                <DateTimePicker
                  label="Publish Date & Time"
                  v-model="form.published_at"
                  :required="true"
                  :error="errors.published_at?.[0]"
                />
                <p class="text-xs text-slate-400 mt-2">
                  The post will automatically go live at the selected date and time.
                </p>
              </div>
            </transition>

        <!-- </div> -->

        <!-- Thumbnail upload -->
         <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">
            Thumbnail
          </label>

          <!-- Preview -->
          <div v-if="thumbnailPreview" class="mb-3 relative inline-block">
            <img
              :src="thumbnailPreview"
              alt="Preview"
              class="w-full h-48 object-cover rounded-xl border border-slate-700" />

            <button
              @click="removeThumbnail"
              class="absolute top-2 right-2 p-1.5 bg-red-600 hover:bg-red-700
                    text-white rounded-lg transition-colors"
              title="Remove thumbnail"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Upload zone-->

          <div
            v-if="!thumbnailPreview"
            @click="triggerFileInput"
            class="flex flex-col items-center jusfity-center gap-2 w-full
                  h-32 border-2 border-dashed border-slate-600 rounded-xl
                  text-slate-400 hover:border-indigo-500 hover:text-indigo-400
                  cursor-pointer transition-colors">
            <PhotoIcon class="w-8 h-8" />
            <span class="text-sm font-medium">Click to upload thumbnail</span>
            <span class="text-xs text-slate-500">JPG, PNG or WEBP - max 2MB</span>
          </div>

          <!-- Replace button when preview exists -->
           <button
              v-if="thumbnailPreview"
              @click="triggerFileInput"
              class="mt-2 flex items-center gap-2 px-4 py-2 border border-slate-600
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
                @change="onThumbnailChange" />

         </div>

         <!-- Validation hint -->
          <div v-if="!isFormValid && missingFields.length > 0"
            class="px-4 py-3 bg-amber-500/10 border border-amber-500/20
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
      <div class="sticky bottom-0 px-6 py-4 border-t border-slate-700 bg-slate-800 flex gap-3 justify-end">
        <button @click="emit('close')"
          class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
               hover:bg-slate-700 text-sm font-medium transition-colors">
          Cancel
        </button>

        <!-- Disabled until form is valid -->
        <button
        @click="handleSubmit"
        :disabled="loading || !isFormValid || (isEdit && !isFormDirty)"
        :title="!isFormValid || (isEdit && !isFormDirty) ? 'Make at least one change before updating' : ''"
        :class="['px-6 py-2.5 rounded-xl text-sm font-medium transition-colors',
          isFormValid && (!isEdit || isFormDirty) && !loading
            ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer'
            : 'bg-slate-600 text-slate-400 cursor-not-allowed opacity-60']"
      >
        {{ loading ? 'Saving...' : (isEdit ? 'Update Post' : 'Publish Post') }}
      </button>
      </div>
  </div>
</template>
