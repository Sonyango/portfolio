<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import AdminPostForm from '@/components/admin/AdminPostForm.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const { get, del } = useApi()
const uiStore   = useUiStore()
const posts     = ref([])
const showForm  = ref(false)
const editItem  = ref(null)
const deleteId  = ref(null)
const loading   = ref(false)

const statusColors  = {
  published: 'bg-green-500/10 text-green-400',
  draft:     'bg-slate-600/50 text-slate-400',
  Scheduled: 'bg-amber-500/10 text-amber-400',
}

async function fetchPosts() {
  loading.value = true
  const { data }  = await get('/admin/posts')
  if (data) posts.value = data.data ?? []
  loading.value = false
}

function openCreate() { editItem.value = null; showForm.value = true }
function openEdit(post) { editItem.value = { ...post }; showForm.value = true }
function confirmDelete(id) { deleteId.value = id }

async function handleDelete() {
  if (!deleteId.value) return
  const { success } = await del(`/admin/posts/${deleteId.value}`)
  if (success) {
    uiStore.success('Post deleted successfully.')
    deleteId.value = null
    await fetchPosts()
  }
}

function onSaved() {
  showForm.value = false;
  editItem.value = null
  fetchPosts()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(fetchPosts)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Blog Posts" subtitle="Write and manage your blog content">
      <template #action>
        <button @click="openCreate"
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                 text-white px-4 py-2.5 rounded-xl text-sm font-medium
                 transition-colors">
          <PlusIcon class="w-4 h-4" /> New Post
        </button>
      </template>
    </PageHeader>

    <!-- Posts table -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">

      <!-- Loading -->
       <div v-if="loading" class="p-8 text-center text-slate-400">
        Loading posts...
       </div>

       <!-- Empty -->
      <div v-else-if="posts.length === 0"
        class="p-12 text-center text-slate-400">
        <p class="text-lg font-medium">No posts yet.</p>
        <p class="text-sm mt-1">Click "New Post" to write your first blog post.</p>
      </div>

      <!-- Table -->
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-slate-700">
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400
                       uppercase tracking-wider">Title</th>
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400
                       uppercase tracking-wider">Status</th>
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400
                       uppercase tracking-wider">Published</th>
            <th class="px-6 py-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="post in posts" :key="post.id"
            class="hover:bg-slate-700/30 transition-colors">
            <td class="px-6 py-4">
              <p class="text-white font-medium text-sm">{{ post.title }}</p>
              <p class="text-slate-400 text-xs mt-0.5">{{ post.slug }}</p>
            </td>
            <td class="px-6 py-4">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium capitalize',
                             statusColors[post.status]]">
                  {{ post.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-400 text-sm">
              {{ formatDate(post.published_at)}}
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2 justify-end">
                <button @click="openEdit(post)"
                  class="p-2 text-slate-400 hover:text-white hover:bg-slate-700
                         rounded-lg transition-colors">
                    <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="confirmDelete(post.id)"
                  class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10
                         rounded-lg transition-colors">
                    <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminPostForm
      v-if="showForm"
      :post="editItem"
      @saved="onSaved"
      @close="showForm = false"
    />

    <ConfirmModal
      :show="!!deleteId"
      title="Delete post?"
      message="This will permanently delete the post."
      @confirm="handleDelete"
      @cancel="deleteId = null"
    />
  </AdminLayout>
</template>
