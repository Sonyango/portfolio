<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue'
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { PlusIcon, PencilIcon, TrashIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';


const { get, post, put, del } = useApi()
const uiStore     = useUiStore()
const experiences = ref([])
const showForm    = ref(false)
const deleteId    = ref(null)
const editId      = ref(null)

const form = ref({
  company: '', role: '', description: '',
  start_date: '', end_date: '', current: false, location: ''
})

async function fetchExperiences() {
  const { data } = await get('/admin/experiences')
  if (data) experiences.value = data.data
}

function openCreate() {
  editId.value  = null
  form.value    = {
    company: '', role: '', description: '',
    start_date: '', end_date: '', current: false, location: ''
  }
  showForm.value = true
}

function openEdit(exp) {
  editId.value    = exp.id
  form.value      = {
    company:      exp.company     || '',
    role:         exp.role        || '',
    description:  exp.description || '',
    start_date:   exp.start_date  || '',
    end_date:     exp.end_date    || '',
    current:      exp.current     || false,
    location:     exp.location    || '',
  }
  showForm.value = true
}

async function handleSubmit() {
  const result = editId.value
    ? await put(`/admin/experiences/${editId.value}`, form.value)
    : await post('/admin/experiences', form.value)

  if (result.success) {
    uiStore.success(editId.value ? 'Experience updated.' : 'Experience added.')
    showForm.value = false
    await fetchExperiences()
  }
}

async function handleDelete() {
  const { success } = await del(`/admin/experiences/${deleteId.value}`)
  if (success) {
    uiStore.success('Experience deleted.')
    deleteId.value = null
    await fetchExperiences()
  }
}

onMounted(fetchExperiences)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Experience" subtitle="Manage your work history timeline">
      <template #action>
        <button @click="openCreate"
        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                 text-white px-4 py-2.5 rounded-xl text-sm font-medium
                 transition-colors">
        <PlusIcon class="w-4 h-4" /> Add Experience
        </button>
      </template>
    </PageHeader>

    <!-- Form -->
     <div v-if="showForm"
      class="bg-slate-800 rounded-2xl border border-slate-700 p-6 mb-6">
      <h3 class="text-white font-semibold mb-4">
        {{ editId ? 'Edit Experience' : 'New Experience' }}
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <FormInput label="Company" v-model="form.company"
          placeholder="Acme Corp" :required="true" />
        <FormInput label="Role"   v-model="form.role"
          placeholder="Software Developer"  :required="true" />
        <FormInput label="Location" v-model="form.location"
          placeholder="Nairobi, Kenya" />
        <FormInput label="Start Date" v-model="form.start_date"
          type="date" :required="true" />
        <FormInput label="End Date" v-model="form.end_date"
          type="date" :disabled="form.current" />
        <div class="flex items-center gap-2 mt-6">
          <input type="checkbox" v-model="form.current" id="current"
            class="w-4 h-4 accent-indigo-600" />
            <label for="current" class="text-sm text-slate-300">
              Currently working here
            </label>
        </div>
      </div>
      <div class="mt-4">
        <FormTextarea label="Description" v-model="form.description"
          placeholder="What were your achievements here?" :rows="3" />
      </div>
      <div class="flex gap-3 mt-4">
        <button @click="handleSubmit"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                 text-white rounded-xl text-sm font-medium transition-colors">
          <CheckIcon class="w-4 h-4" /> Save
        </button>
        <button @click="showForm = false"
          class="flex items-center gap-2 px-4 py-2 border border-slate-600
                 text-slate-300 hover:bg-slate-700 rounded-xl text-sm
                 font-medium transition-colors">
          <XMarkIcon class="w-4 h-4" /> Cancel
        </button>
      </div>
    </div>

    <!-- Timeline-->
     <div class="space-y-4">
      <div v-if="experiences.length === 0"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-12
               text-center text-slate-400">
        No experience entries yet.
      </div>

      <div v-for="exp in experiences" :key="exp.id"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-5
               flex items-start gap-4">
        <div class="w-2 h-2 rounded-full bg-indigo-500 mt-2 shrink-0" />
        <div class="flex-1">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-white font-semibold">{{ exp.role }}</p>
              <p class="text-indigo-400 text-sm">{{ exp.company }}</p>
              <p class="text-slate-400 text-xs mt-1">
                {{ exp.start_date }} -
                {{ exp.current ? 'Present' : exp.end_date }}
                <span v-if="exp.location"> . {{ exp.location }}</span>
              </p>
            </div>
            <div class="flex gap-2">
              <button @click="openEdit(exp)"
                class="p-2 text-slate-400 hover:text-white hover:bg-slate-700
                       rounded-lg transition-colors">
                <PencilIcon class="w-4 h-4" />
              </button>
              <button @click="deleteId = exp.id"
                class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10
                       rounded-lg transition-colors">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <p v-if="exp.description" class="text-slate-300 text-sm mt-2">
            {{ exp.description }}
          </p>
        </div>
      </div>
     </div>

     <ConfirmModal
      :show="!!deleteId"
      title="Delete experience?"
      message="This experience entry will be permanently removed."
      @confirm="handleDelete"
      @cancel="deleteId = null"
    />
  </AdminLayout>
</template>
