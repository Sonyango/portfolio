<script setup>
import { ref, watch, computed } from 'vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import TiptapEditor from '@/components/admin/TiptapEditor.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { useSlug } from '@/composables/useSlug';
import { XMarkIcon, PhotoIcon } from '@heroicons/vue/24/outline';

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

const statusOptions = [
  { value:  'draft',      label: 'Draft' },
  { value:  'published',  label:  'Published' },
  { value:  'scheduled',  label:  'Scheduled' },
]

watch(()  => props.post, (p) => {
  if (p) {
    form.value  = {
      title:        p.title         || '',
      slug:         p.slug          || '',
      content:      p.content       || '',
      excerpt:      p.excerpt       || '',
      status:       p.status        || 'draft',
      published_at: p.published_at  || '',
    }
    thumbnailPreview.value  = p.thumbnail  || null
  }
}, { immediate: true })

watch(() => form.value.title, (title) => {
  if (!isEdit.value) form.value.slug = generateSlug(title)
})

function onThumbnaiChange(e) {
  const file = e.target.files[0]
  if (!file) return
  thumbnailFile.value = file
  thumbnailPreview.value = URL.createObjectURL(file)
}

async function handleSubmit() {
  const formData = new FormData()

  Object.entries(form.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined ) {
      formData.append(key, value)
    }
  })

  if (thumbnailFile.value) {
    formData.append('thumbnail', thumbnailFile.value)
  }

  let result
  if (isEdit.value) {
    formData.append('_method', 'PUT')
    result = await apiPost(`/admin/posts/${props.post.id}`, formData)
  } else {
    result = await apiPost('/admin/posts', formData)
  }

  if (result.success) {
    uiStore.success(isEdit.value ? 'Post updated.' : 'Post created.')
    emit('saved')
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/60 z-40" @click="emit('close')" />

  <div class="fixed right-0 top-0 h-full w-full max-w-3xl bg-slate-800
              border-l border-slate-700 z-50 overflow-y-auto">

      <div class="flex items-center justify-between px-6 py-4
                border-b border-slate-700 sticky top-0 bg-slate-800 z-10">
          <h3 class="text-lg font-semibold text-white">
            {{ isEdit ? 'Edit Post' : 'New Post' }}
          </h3>
          <button @click="emit('close')" class="p-2 text-slate-400 hover:text-white rounded-lg">
            <XMarkIcon class="w-5 h-5" />
          </button>
      </div>

      <div class="p-6 space-y-5">
        <FormInput
          label="Title" v-model="form.title"
          placeholder="Post title" :required="true"
          :error="errors.title?.[0]"
        />

        <FormInput
          label="Slug" v-model="form.slug"
          placeholder="post-slug"
          :error="errors.slug?.[0]"
        />

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">
            Content <span class="text-red-400">*</span>
          </label>
          <TiptapEditor v-model="form.content" />
          <p v-if="errors.content?.[0]" class="mt-1 text-xs text-red-400">
            {{ errors.content[0] }}
          </p>
        </div>

        <FormTextarea
          label="Excerpt" v-model="form.excerpt"
          placeholder="Short summary shown in blog listing..."
          :rows="3"
          :error="errors.excerpt?.[0]"
        />

        <div class="grid grid-cols-2 gap-4">
          <FormSelect
            label="Status"  v-model="form.status"
            :options="statusOptions"
          />
          <FormInput
          v-if="form.status === 'scheduled'"
          label="Publish Date"  v-model="form.published_at"
          type="datetime-local"
          :error="errors.published_at?.[0]"
          />
        </div>

        <!-- Thumbnail -->
         <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">
            Thumbnail
          </label>
          <div v-if="thumbnailPreview" class="mb-3">
            <img :src="thumbnailPreview" alt="Preview"
              class="w-full h-40 object-cover rounded-xl border border-slate-700" />
          </div>
          <label class="flex items-center justify-center gap-2 w-full h-24
                      border-2 border-dashed border-slate-600 rounded-xl
                      text-slate-400 hover:border-indigo-500 hover:text-indigo-400
                      cursor-pointer transition-colors">
              <PhotoIcon class="w-5 h-5" />
              <span class="text-sm">Click to upload thumbnail</span>
              <input type="tile" accept="image/*" class="hidden"
                @change="onThumbnaiChange" />
          </label>
         </div>
      </div>

      <div class="sticky bottom-0 px-6 py-4 border-t border-slate-700 bg-slate-800 flex gap-3 justify-end">
        <button @click="emit('close')"
          class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
               hover:bg-slate-700 text-sm font-medium transition-colors">
          Cancel
        </button>
        <button @click="handleSubmit" :disabled="loading"
          class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700
               disabled:opacity-50 text-white text-sm font-medium transition-colors">
          {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Publish') }}
        </button>
      </div>
  </div>
</template>
