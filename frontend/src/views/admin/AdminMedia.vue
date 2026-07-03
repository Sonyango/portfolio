<script setup>
import { ref, onMounted, computed } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import {
  CloudArrowUpIcon,
  TrashIcon,
  ClipboardDocumentIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
  } from '@heroicons/vue/24/outline';

  const { get, del, loading } = useApi();
  const uiStore = useUiStore();
  const mediaItems = ref([]);
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
    return mediaItems.value.filter(item => {
      const matchesSearch = search.value
        ? item.file_name.toLowerCase().includes(search.value.toLowerCase())
        : true;

      const matchesFilter = filter.value === 'all'
        ? true
        : filter.value === 'image'
          ? item.mime_type.startsWith('image/')
          : !item.mime_type.startsWith('image/')

      return matchesSearch && matchesFilter;
    })
  })

  // Fetch all media
  async function fetchMedia() {
    const { data } = await get('/admin/media');
    if (data) mediaItems.value = data.data ?? [];
  }

  // Upload files
  async function uploadFiles(files) {
    if (!files || files.length === 0) return;
    uploading.value = true;

    for (const file of files) {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('collection', 'general')

      try {
        const token = localStorage.getItem('admin_token')
        const response = await fetch('/api/admin/media', {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
          body: formData,
        })

        if (response.ok) {
          uiStore.success(`${file.name} uploaded.`)
        } else {
          uiStore.error(`Failed to upload ${file.name}.`)
        }
      } catch {
        uiStore.error(`Error uploading ${file.name}.`)
      }
    }

    uploading.value = false
    await fetchMedia()
  }

  // Handle file input change
  function onFileChange(e) {
    uploadFiles(e.target.files)
  }

  // Handle drag and drop
  function onDrop(e) {
    dragOver.value = false
    uploadFiles(e.dataTransfer.files)
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

  // Open details panel
  function openDetail(item) {
    selected.value = item
  }

  // Delete media item
  async function handleDelete() {
    const { success } = await del(`/admin/media/${deleteId.value}`)
    if (success) {
      uiStore.success('File deleted.')
      deleteId.value = null
      selected.value = null
      await fetchMedia()
    }
  }

  // Format file size
  function formatSize(bytes) {
    if (!bytes) return '-'
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB'
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB'
    return bytes + ' B'
  }

  // Check if file is an image
  function isImage(mimeType) {
    return mimeType?.startsWith('image/')
  }

  onMounted(fetchMedia)
</script>
<template>
  <AdminLayout>
    <PageHeader title="Media Library" subtitle="Upload and manage your files">
      <template #action>
        <label
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                 text-white px-4 py-2.5 rounded-xl text-sm font-medium
                 transition-colors cursor-pointer">
          <CloudArrowUpIcon class="w-4 h-4" />
          Upload Files
          <input type="file" multiple accept="image/*, .pdf,.doc,.docx"
          class="hidden" @change="onFileChange" />
        </label>
      </template>
    </PageHeader>

    <div class="flex gap-6">

      <!-- Left: Grid panel-->
       <div class="flex-1 min-w-0">

        <!-- Search & filter -->
         <div class="flex gap-3 mb-6">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="search"
              type="text"
              placeholder="Search files..."
              class="w-full bg-slate-800 border border-slate-700 rounded-xl
                     pl-9 pr-4 py-2.5 text-white placeholder-slate-500 text-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div class="flex gap-1 bg-slate-800 border border-slate-700 rounded-xl p-1">
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
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
            :class="['border-2 border-dashed rounded-2xl p-8 mb-6 text-center',
                   'transition-colors',
                   dragOver
                     ? 'border-indigo-500 bg-indigo-500/5'
                     : 'border-slate-700 hover:border-slate-600']">
            <CloudArrowUpIcon class="w-8 h-8 mx-auto text-slate-500 mb-2" />
            <p class="text-slate-400 text-sm">Drag and drop files here, or
              <label class="text-indigo-400 hover:text-indigo-300 cursor-pointer underline">
                browse
                <input type="file" multiple accept="image/*,.pdf,.doc,.docx"
                  class="hidden" @change="onFileChange" />
              </label>
            </p>
            <p class="text-slate-500 text-xs mt-1">Supports: JPG, PNG, PDF, DOC, DOCX, - Max 5MB per file.</p>
            <div v-if="uploading"
              class="mt-3 text-indigo-400 text-sm animate-pulse">
              Uploading...
            </div>
          </div>

          <!-- Empty state -->
           <div v-if="filteredMedia.length === 0 && !loading"
            class="bg-slate-800 rounded-2xl border border-slate-700 p-12 text-center text-slate-400">
            <CloudArrowUpIcon class="w10 h-10 mx-auto mb-3 opacity-30" />
            <p class="font-medium">No files found</p>
            <p class="text-sm mt-1">Upload files using the button above.</p>
          </div>

          <!-- Media grid -->
           <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            <div
              v-for="item in filteredMedia"
              :key="item.id"
              @click="openDetail(item)"
              :class="['bg-slate-800 rounded-xl border overflow-hidden cursor-pointer',
                     'transition-all hover:scale-105',
                     selected?.id === item.id
                       ? 'border-indigo-500 ring-1 ring-indigo-500'
                       : 'border-slate-700 hover:border-slate-600']">
              <!-- Image preview -->
               <div class="aspect-square bg-slate-900 flex items-center justify-center overflow-hidden">
                <img
                  v-if="isImage(item.mime_type)"
                  :src="item.url"
                  :alt="item.file_name"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div class="flex flex-col items-center justify-center p-3">
                  <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-slate-400 text-xs font-bold mb-1">
                    {{ item.mime_type?.split('/')[1]?.toUpperCase() || 'FILE' }}
                  </div>
                </div>
               </div>

               <!-- File name -->
                <div class="p-2">
                  <p class="text-slate-300 text-xs truncate">{{ item.file_name }}</p>
                  <p class="text-slate-500 text-xs">{{ formatSize(item.file_size) }}</p>
                </div>
            </div>
           </div>
       </div>

       <!-- Right detail panel -->
        <div
          v-if="selected"
          class="w-72 shrink-0 bg-slate-800 border border-slate-700 rounded-2xl p-5 h-fit sticky top-8">
          <!-- Close -->
           <div class="flex items-center justify-between mb-4">
            <h3 class="text-white font-semibold text-sm">File Details</h3>
            <button @click="selected = null"
              class="p-1 text-slate-400 hover:text-white rounded-lg">
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
                class="text-slate-400 text-xs font-bold">
                {{ selected.mime_type?.split('/')[1]?.toUpperCase() || 'FILE' }}
              </div>
            </div>

            <!-- Info -->
             <div class="space-y-2 mb-4 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-400">Name</span>
                <span class="text-slate-200 truncate max-w-36 text-right">
                  {{ selected.file_name }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Type</span>
                <span class="text-slate-200">{{ selected.mime_type }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Size</span>
                <span class="text-slate-200">{{ formatSize(selected.file_size) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Uploaded</span>
                <span class="text-slate-200">
                  {{ new Date(selected.created_at).toLocaleString() }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Collection</span>
                <span class="text-slate-200 capitalize">{{ selected.collection }}</span>
              </div>
             </div>

            <!-- URL Copy -->
             <div class="mb-4">
              <label class="block text-xs text-slate-400 mb-1">File URL</label>
                <div class="flex gap-2">
                  <input
                    :value="selected.url"
                    readonly
                    class="flex-1 min-w-0 bg-slate-900 border border-slate-700
                      rounded-lg px-2 py-1.5 text-xs text-slate-300
                      focus:outline-none truncate"
                  />
                  <button
                    @click="copyUrl(selected.url)"
                    class="p-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300
                        rounded-lg transition-colors shrink-0">
                    <ClipboardDocumentIcon class="w-4 h-4" />
                  </button>
                </div>
             </div>

             <!-- Actions -->
              <div class="flex gap-2">

                <a :href="selected.url"
                    target="_blank"
                    class="flex-1 text-center py-2 border border-slate-600
                   text-slate-300 hover:bg-slate-700 rounded-xl text-xs
                   font-medium transition-colors">View</a>
                <button
                  @click="deleteId = selected.id"
                  class="flex items-center gap-1 px-3 py-2 bg-red-500/10
                   hover:bg-red-500/20 text-red-400 rounded-xl text-xs
                   font-medium transition-colors">
                   <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
        </div>
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
