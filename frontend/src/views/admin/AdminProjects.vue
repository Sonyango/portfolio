<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import AdminProjectForm from '@/components/admin/AdminProjectForm.vue'
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const { get, del, loading } = useApi()
const uiStore   = useUiStore()
const projects  = ref([])
const showForm  = ref(false)
const editItem  = ref(null)
const deleteId  = ref(null)

async function fetchProjects() {
  const { data } =  await get('/admin/projects')
  if (data) projects.value = data.data
}

function openCreate() {
  editItem.value = null
  showForm.value = true
}

function openEdit(project) {
  editItem.value  = project
  showForm.value  = true
}

function confirmDelete(id) {
  deleteId.value = id
}

async function handleDelete() {
  const { success } = await del(`/admin/projects/${deleteId.value}`)
  if (success) {
    uiStore.success('Project deleted.')
    deleteId.value = null
    await fetchProjects()
  }
}

function onSaved() {
  showForm.value = false
  fetchProjects()
}

onMounted(fetchProjects)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Projects" subtitle="Manage your portfolio projects">
      <template #action>
        <button
          @click="openCreate"
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                  text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
          <PlusIcon class="w-4 h-4" /> Add Project
        </button>
      </template>
    </PageHeader>

    <!-- Projects table-->
     <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-400">
        Loading...
      </div>

      <div v-else-if="projects.length === 0"
        class="p-12 text-center text-slate-400">
        <p class="text-lg font-medium">No projects yet</p>
        <p class="text-sm mt-1">Click "Add Project" to create your first project.</p>
      </div>

      <table class="w-full">
        <thead>
          <tr class="border-b border-slate-700">
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Project</th>
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Category</th>
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Featured</th>
            <th class="px-6 py-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="project in projects" :key="project.id"
            class="hover:bg-slate-700/30 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <img
                  v-if="project.thumbnail"
                  :src="project.thumbnail"
                  :alt="project.title"
                  class="w-10 h-10 rounded-lg object-cover" />
                <div v-else class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-slate-400 text-xs">
                  IMG
                </div>
                <div>
                  <p class="text-white font-medium text-sm">{{ project.title }}</p>
                  <p class="text-slate-400 text-xs">{{ project.slug }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-slate-300 text-sm">
              {{ project.category || '-' }}
            </td>
            <td class="px-6 py-4">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium',
                project.published
                  ? 'bg-green-500/10 text-green-400'
                  : 'bg-slate-600/50 text-slate-400']">
                  {{ project.published ? 'Published' : 'Draft' }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span v-if="project.featured"
                class="px-2 5 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400">
                Featured
              </span>
              <span v-else class="text-slate-500 text-xs">-</span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2 justify-end">
                <button
                  @click="openCreate(project)"
                  class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="confirmDelete(project.id)"
                  class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
     </div>

     <!--Project form drawer -->
     <AdminProjectForm
        v-if="showForm"
        :project="editItem"
        @saved="onSaved"
        @close="showForm = false"
     />

     <!--Delete confirm-->
     <ConfirmModal
        :show="!!deleteId"
        title="Delete project?"
        message="This will permanently delete the project and its images."
        @confirm="handleDelete"
        @cancel="deleteId = null"
     />
  </AdminLayout>
</template>
