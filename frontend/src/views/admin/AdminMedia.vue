<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import api  from '@/api/index.js';
import {
  CloudArrowUpIcon,
  TrashIcon,
  ClipboardDocumentIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
  ArrowPathIcon,
  } from '@heroicons/vue/24/outline';

  // Composables
  const { del } = useApi();
  const uiStore = useUiStore();

  // State
  const mediaItems = ref([]);
  const fetchLoading = ref(false);
  const deleteId   = ref(null);
  const selected   = ref(null);
  const search     = ref('');
  const filter     = ref('all');
  const uploading  = ref(false);
  const dragOver   = ref(false);

  // Filter options
  const filterOptions = [
    { value: 'all', label: 'All files' },
    { value: 'image', label: 'Images' },
    { value: 'other', label: 'Other' },
  ]

  // Filtered media
  const filteredMedia = computed(() => {
    let items = mediaItems.value

    // Search filter
    if (search.value.trim()) {
      const term = search.value.toLowerCase()
      items = items.filter(item => item.file_name?.toLowerCase().includes(term))
    }

    // Type filter
    if (filter.value !== 'all') {
      if (filter.value === 'image') {
        items = items.filter(item => item.mime_type?.startsWith('image/'))
      } else {
        items = items.filter(item => !item.mime_type?.startsWith('image/'))
      }
    }
    return items
  })

  // Keep selected in sync when list changes
  watch(mediaItems, () => {
    if (selected.value) {
      const stillExists = mediaItems.value.find(m => m.id === selected.value.id)
      if (!stillExists) {
        selected.value = null
      } else {
        // Refresh the selected item data
        selected.value = stillExists
      }
    }
  })

  // Fetch all media
  async function fetchMedia() {
    fetchLoading.value = true
    try {
      const { data } = await api.get('/admin/media');
      // Handle both { data: [...] } and [...] shapes
      if (Array.isArray(data)) {
        mediaItems.value = data
      } else if (data && Array.isArray(data.data)) {
        mediaItems.value = data.data
      } else {
        mediaItems.value = []
      }
    } catch (error) {
      uiStore.error('failed to load media files.')
      mediaItems.value = []
    } finally {
      fetchLoading.value = false
    }
  }

  // Upload files. Use Axios instance (api) so auth headers are always sent correctly
  async function uploadFiles(files) {
    if (!files || files.length === 0) return;

    const allowed = [
      'image/jpeg', 'image/jpg', 'img/png', 'image/webp', 'image/gif', 'application/pdf',
      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]

    const maxSize = 5 * 1024 * 1024 // 5MB

    for (const file of Array.from(files)) {
      if (!allowed.includes(file.type)) {
        uiStore.error(`"${file.name}" is not a supported file type.`)
        continue
      }
      if (file.size > maxSize) {
        uiStore.error(`"${file.name}" exceeds the 5MB limit.`)
        continue
      }
      uploading.value = true

      try {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('collection', 'general')

        // Using Axios instance, auth token is injected by the interceptor
        const response = await api.post('/admin/media', formData)

        uiStore.success(`"${file.name}" uploaded successfully.`)

        // Prepend the new item to the list
        const newItem = response.data?.data
        if (newItem) {
          mediaItems.value = [newItem, ...mediaItems.value]
          selected.value = newItem
        }
      } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.errors?.file?.[0]
          || `Failed to upload "${file.name}".`
        uiStore.error(msg)
      } finally {
        uploading.value = false
      }
    }
  }

  // Handle file input change
  function onFileChange(e) {
    if (e.target.files?.length) {
      uploadFiles(e.target.files)
      // Reset input so another file can be uploaded
      e.target.value = ''
    }
  }

  // Handle drag and drop
  function onDragOver(e) {
    e.preventDefault()
    dragOver.value = true
  }

  function onDragLeave() {
    dragOver.value = false
  }

  function onDrop(e) {
    e.preventDefault()
    dragOver.value = false
    if (e.dataTransfer?.files?.length) {
      uploadFiles(e.dataTransfer.files)
    }
  }

  // Copy file URL to clipboard
  async function copyUrl(url) {
    try {
      await navigator.clipboard.writeText(url)
      uiStore.success('URL copied to clipboard.')
    } catch {
      uiStore.error('Failed to copy URL.')
    }
  }

  // Select item
  function openDetail(item) {
    selected.value = selected.value?.id === item.id ? null : item
  }

  // Delete media item
  function confirmDelete(id, e) {
    e?.stopPropagation()
    deleteId.value = id
  }

  async function handleDelete() {
    if (!deleteId.value) return

    const { success } = await del(`/admin/media/${deleteId.value}`)
    if (success) {
      uiStore.success('File deleted.')
      mediaItems.value = mediaItems.value.filter(m => m.id !== deleteId.value)
      if (selected.value?.id === deleteId.value) {
        selected.value = null
      }
    }
    deleteId.value = null
  }

  // Format file size
  // function formatSize(bytes) {
  //   if (!bytes) return '-'
  //   if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB'
  //   if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB'
  //   return bytes + ' B'
  // }

  // Check if file is an image
  function isImage(mimeType) {
    return mimeType?.startsWith('image/')
  }

  function fileExtension(mimeType) {
    const map = {
      'application/pdf': 'PDF',
      'application/msword': 'DOC',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
      'image/jpeg': 'JPG',
      'image/png':  'PNG',
      'image/webp': 'WEBP',
      'image/gif':  'GIF',
    }
    return map[mimeType] ?? mimeType?.split('/')[1]?.toUpperCase() ?? 'FILE'
  }

  onMounted(fetchMedia)
</script>
<template>
  <AdminLayout>
    <PageHeader title="Media Library" subtitle="Upload and manage your files">
      <template #action>
        <div class="flex items-center gap-2">
          <!-- Refresh button -->
           <button
              @click="fetchMedia"
              :disabled="fetchLoading"
              class="p-2.5 border border-slate-700 hover:border-slate-500 text-slate-400 hover:text-white rounded-xl transition-colors"
              title="Refresh"
           >
            <ArrowPathIcon class="w-4 h-4" :class="fetchLoading ? 'animate-spin' : ''" />
           </button>
           <!-- Upload button -->
            <label
              class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer">
              <CloudArrowUpIcon class="w-4 h-4" />
              Upload Files
              <input type="file" multiple accept="image/*, .pdf,.doc,.docx"
              class="hidden" @change="onFileChange" />
          </label>
        </div>
      </template>
    </PageHeader>

    <div class="flex gap-6">

      <!-- Left: Grid panel-->
       <div class="flex-1 min-w-0">

        <!-- Search & filter -->
         <div class="flex gap-3 mb-5">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
            <input
              v-model="search"
              type="text"
              placeholder="Search files by name..."
              class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-9 pr-4 py-2.5 text-white placeholder-slate-500 text-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
            <button
              v-if="search"
              @click="search = ''"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
          <!-- Type filter -->
          <div class="flex gap-1 bg-slate-800 border border-slate-700 rounded-xl p-1 shrink-0">
            <button
              v-for="opt in filterOptions"
              :key="opt.value"
              @click="filter = opt.value"
              :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                filter === opt.value
                  ? 'bg-indigo-600 text-white'
                  : 'text-slate-400 hover:text-white']">
              {{ opt.label }}
            </button>
          </div>
         </div>

         <!-- Drag & Drop upload zone -->
          <div
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            :class="['border-2 border-dashed rounded-2xl p-6 mb-5 text-center',
                   'transition-all duration-200',
                   dragOver
                     ? 'border-indigo-500 bg-indigo-500/5 scale-[1.01]'
                     : 'border-slate-700 hover:border-slate-600']">
            <CloudArrowUpIcon class="w-8 h-8 mx-auto text-slate-500 mb-2" />
            <p class="text-slate-400 text-sm">Drag and drop files here, or
              <label class="text-indigo-400 hover:text-indigo-300 cursor-pointer underline transition-colors">
                browse
                <input type="file" multiple accept="image/*,.pdf,.doc,.docx"
                  class="hidden" @change="onFileChange" />
              </label>
            </p>
            <p class="text-slate-500 text-xs mt-1">Supports: JPG, WEBP, PNG, PDF, DOC, DOCX, - Max 5MB per file.</p>
            <div v-if="uploading"
              class="mt-3 flex items-center justify-center gap-2 text-indigo-400 text-sm">
              <span class="w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin" />
              Uploading...
            </div>
          </div>

          <!-- Loading state -->
           <div
            v-if="fetchLoading && mediaItems.length === 0" class="py-20 text-center">
            <span class="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin inline-block mb-3" />
            <p class="text-slate-400 text-sm">Loading media files...</p>
          </div>

          <!-- Empty state -->
           <div
            v-else-if="!fetchLoading && filteredMedia.length === 0"
            class="bg-slate-800 rounded-2xl border border-slate-700 py-16 text-center">
            <CloudArrowUpIcon class="w-10 h-10 mx-auto mb-3 text-slate-600 opacity-30" />
            <p class="text-slate-400 font-medium">
              {{ search || filter !== 'all' ? 'No files matches your filter' : 'No files uploaded yet.' }}
            </p>
            <p class="text-slate-500 text-sm mt-1">
              {{ search || filter !== 'all' ? 'Try a different search or filter.' : 'Upload files using the button above.' }}
            </p>
          </div>

          <!-- Media grid -->
           <div
              v-else
              class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            <div
              v-for="item in filteredMedia"
              :key="item.id"
              @click="openDetail(item)"
              :class="['bg-slate-800 rounded-xl border overflow-hidden cursor-pointer',
                     'cursor-pointer transition-all duration-200', 'hover:border-slate-500 hover:shadow-lg',
                     selected?.id === item.id ? 'border-indigo-500 ring-1 ring-indigo-500/30' : 'border-slate-700 hover:border-slate-600']">
              <!-- Image preview -->
               <div class="aspect-square bg-slate-900 flex items-center justify-center overflow-hidden">
                <img
                  v-if="isImage(item.mime_type)"
                  :src="item.url"
                  :alt="item.file_name"
                  class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                  loading="lazy"
                />
                  <div
                    v-else class="flex flex-col items-center justify-center p-3 gap-1 w-full h-full">
                    <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center">
                      <span class="text-slate-400 text-xs font-bold">
                        {{ fileExtension(item.mime_type) }}
                      </span>
                    </div>
                  </div>
               </div>

               <!-- File info -->
                <div class="p-2">
                  <p class="text-slate-300 text-xs truncate leading-tight" :title="item.file_name">
                    {{ item.file_name }}
                  </p>
                  <p class="text-slate-500 text-xs mt-0.5">
                    {{ item.file_size_human }}
                  </p>
                </div>
            </div>
           </div>

           <!-- Stats bar -->
            <div v-if="mediaItems.length > 0" class="mt-4 text-xs text-slate-500 text-right">
              Showing {{ filteredMedia.length }} of {{ mediaItems.length }} files
            </div>
       </div>

       <!-- Right detail panel -->
        <transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 translate-x-4"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-x-0"
          leave-to-class="opacity-0 translate-x-4"
        >
        <div
          v-if="selected"
          class="w-72 shrink-0 bg-slate-800 border border-slate-700 rounded-2xl p-5 h-fit sticky top-4">
          <!-- Close -->
           <div class="flex items-center justify-between mb-4">
            <h3 class="text-white font-semibold text-sm">File Details</h3>
            <button @click="selected = null"
              class="p-1 text-slate-400 hover:text-white rounded-lg transition-colors" title="Close">
              <XMarkIcon class="w-4 h-4" />
            </button>
           </div>

           <!-- Preview -->
            <div class="aspect-video bg-slate-900 rounded-xl overflow-hidden flex items-center justify-center mb-4">
              <img
                v-if="isImage(selected.mime_type)"
                :src="selected.url"
                :alt="selected.file_name"
                class="w-full h-full object-contain"
              />
              <div v-else
                class="flex flex-col items-center justify-center gap-2">
                <div class="w-14 h-14 rounded-xl bg-slate-700 flex items-center justify-center">
                  <span class="text-slate-300 text-sm font-bold">
                    {{ fileExtension(selected.mime_type) }}
                  </span>
                </div>
                <span class="text-slate-500 text-xs">No preview</span>
              </div>
            </div>

            <!-- File metadata -->
             <div class="space-y-2 mb-4 text-xs">
              <div class="flex justify-between items-start gap-2">
                <span class="text-slate-500 text-xs text-right break-all">Name</span>
                <span class="text-slate-200 text-xs text-right break-all leading-relaxed" :title="selected.file_name">
                  {{ selected.file_name }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Type</span>
                <span class="text-slate-200">{{ selected.mime_type }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 text-xs">Size</span>
                <span class="text-slate-200 text-xs">
                  {{ fileExtension(selected.mime_type) }}
                </span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 text-xs">Uploaded</span>
                <span class="text-slate-200 text-xs">
                  {{ new Date(selected.created_at).toLocaleString() }}
                </span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 text-xs">Collection</span>
                <span class="text-slate-200 text-xs capitalize">
                  {{ selected.collection }}
                </span>
              </div>
             </div>

            <!-- URL Copy -->
             <div class="mb-4">
              <label class="block text-xs text-slate-400 mb-1">File URL</label>
                <div class="flex gap-2">
                  <input
                    :value="selected.url"
                    readonly
                    class="flex-1 min-w-0 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-300 focus:outline-none truncate"
                  />
                  <button
                    @click="copyUrl(selected.url)"
                    class="p-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white rounded-lg transition-colors shrink-0" title="Copy URL">
                    <ClipboardDocumentIcon class="w-4 h-4" />
                  </button>
                </div>
             </div>

             <!-- Actions -->
              <div class="flex gap-2">

                <a :href="selected.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex-1 text-center py-2 border border-slate-600 text-slate-300 hover:text-white hover:border-slate-500
                    rounded-xl text-xs font-medium transition-colors">
                    View
                </a>
                <button
                  @click="confirmDelete(selected.id, $event)"
                  class="flex items-center gap-1 px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300
                  rounded-xl text-xs font-medium transition-colors">
                   <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
        </div>
        </transition>

    </div>

    <!-- Delete confirmation -->
     <ConfirmModal
      :show="!!deleteId"
      title="Delete File"
      message="Are you sure you want to delete this file? This action cannot be undone."
      @confirm="handleDelete"
      @cancel="deleteId = null"
    />
  </AdminLayout>
</template>
