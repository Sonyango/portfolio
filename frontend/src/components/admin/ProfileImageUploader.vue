<script setup>
import { ref, computed, watch } from 'vue';
import { useUiStore } from '@/stores/uiStore';
import api from '@/api/index.js';
import { PhotoIcon, TrashIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  currentImage: { type: String, default: '' },
})

const emit = defineEmits(['uploaded', 'removed'])

const uiStore = useUiStore()
const uploading = ref(false)
const preview = ref(props.currentImage || '')
const dragOver = ref(false)

const hasImage = computed(() => !!preview.value)

async function uploadImage(file) {
  if (!file) return;

  // Validate file type
  if (!['image/jpeg', 'image/png', 'image/webp', 'image/jpg'].includes(file.type)) {
    uiStore.error('Please upload a JPG, PNG, or WEBP image.');
    return;
  }

  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    uiStore.error('Image size should not exceed 2MB.');
    return;
  }

  uploading.value = true;

  try {
    const formData = new FormData();
    formData.append('profile_image', file);

    const token = localStorage.getItem('admin_token');
    const response = await fetch('/api/admin/profile-image', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    })

    const data = await response.json();

    if (response.ok) {
      preview.value = data.url
      emit('uploaded', data)
      uiStore.success('Profile image uploaded successfully.');
    } else {
      uiStore.error(data.message || 'Upload failed.');
    }
  } catch {
    uiStore.error('Upload failed. Please try again.');
  } finally {
    uploading.value = false;
  }
}

async function removeImage() {
  try {
    const token = localStorage.getItem('admin_token');
    const response = await fetch('/api/admin/profile-image', {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    if (response.ok) {
      preview.value = ''
      emit('removed')
      uiStore.success('Profile image removed successfully.');
    }
  } catch {
    uiStore.error('failed to remove image. Please try again.');
  }
}

function onFileChange(e) {
  const file = e.target.files[0];
  if (file) uploadImage(file);
}

function onDrop(e) {
  dragOver.value = false;
  const file = e.dataTransfer.files[0];
  if (file) uploadImage(file);
}

watch(() => props.currentImage, (newVal) => {
  if (newVal && newVal !== preview.value) {
    preview.value = newVal
  }
}, { immediate: true })
</script>

<template>
  <div>
    <label class="block text-sm font-medium text-slate-300 mb-3">
      Profile Image
    </label>

    <!-- Current Image Preview -->
     <div v-if="hasImage" class="mb-4">
      <div class="relative inline-block">
        <img
          :src="preview"
          alt="Profile Image"
          class="w-32 h-32 rounded-full object-cover border-2 border-indigo-500/300" />
        <!-- Remove button -->
         <button @click="removeImage"
            class="absolute -top-2 -right-2 w-7 h-7 bg-red-600 hover:bg-red-700 text-white
                  rounded-full flex items-center justify-center transition-colors shadow-lg">
            <TrashIcon class=" w-3.5 h-3.5" />
          </button>
      </div>
      <p class="text-xs text-slate-400 mt-2">
        Click the button to remove and upload a new image.
      </p>
     </div>

     <!-- File upload area -->
      <div v-if="!hasImage"
        @dragover.prevent="dragOver = true"
        @dragleave="dragOver = false"
        @drop.prevent="onDrop"
        :class="['border-2 border-dashed rounded-xl p-8 text-center transition-colors',
                dragOver ? 'border-indigo-500 bg-indigo-500/5' : 'border-slate-600 hover:border-slate-500']">
          <PhotoIcon class="w-10 h-10 mx-auto text-slate-500 mb-3" />
          <p class="text-slate-400 text-sm mb-1">
            Drag & drop your photo here
          </p>
          <p class="text-slate-500 text-xs mb-4">
            JPG, PNG, or WEBP (Max 2MB)
          </p>

          <label class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                      text-sm font-medium rounded-xl cursor-pointer transition-colors">
                  <ArrowUpTrayIcon class="w-4 h-4" />
                  {{ uploading ? 'Uploading...' : 'Choose Photo' }}
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    :disabled="uploading"
                    @change="onFileChange"
                    />
          </label>
      </div>

      <!-- Replace button when image exists -->
       <div v-if="hasImage" class="mt-3">
        <label class="inline-flex items-center gap-2 px-4 py-2 border border-slate-600 hover:border-slate-500
                    text-slate-300 hover:text-white text-sm font-medium round-xl cursor-pointer transition-colors">
                <ArrowUpTrayIcon class="w-4 h-4" />
                {{ uploading ? 'Uploading...' : 'Replace Photo' }}
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="hidden"
                  :disabled="uploading"
                  @change="onFileChange"
                  />
        </label>
       </div>

       <!-- Upload progress indicator -->
        <div v-if="uploading"
          class="mt-3 flex items-center gap-2 text-sm text-indigo-400">
          <div class="w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin" />
          Uploading...
        </div>
  </div>
</template>
