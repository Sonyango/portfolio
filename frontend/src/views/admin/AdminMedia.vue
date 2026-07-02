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
    <div>
      <h2 class="text-2xl font-bold text-white mb-2">Admin Media</h2>
      <p class="text-slate-400">Coming in Sprint 4.</p>
    </div>
  </AdminLayout>
</template>
