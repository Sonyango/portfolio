<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import FormInput from '@/components/admin/FormInput.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { PlusIcon, TrashIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const { get, post, put, del } = useApi()
const uiStore   = useUiStore()
const skills    = ref([])
const showForm  = ref(false)
const deleteId  = ref(null)

const form  = ref({
  name: '', category: '', proficiency: 80, icon: '', order: 0
})

const editId  = ref(null)

const categoryOptions = [
  'Frontend', 'Backend', 'Database', 'DevOps', 'ICT', 'Networking'
]

async function fetchSkills() {
  const { data }  = await get('/admin/skills')
  if (data) skills.value = data.data
}

function openCreate() {
  editId.value  = null
  form.value    = { name: '', category: '', proficiency: 80, icon: '', order: 0 }
  showForm.value = true
}

function openEdit(skill) {
  editId.value    = skill.id
  form.value      = { ...skill }
  showForm.value  = true
}

async function handleSubmit() {
  const result  = editId.value
    ? await put(`/admin/skills/${editId.value}`, form.value)
    : await post('/admin/skills', form.value)

  if (result.success) {
    uiStore.success(editId.value ? 'Skill updated.' : 'Skill added.')
    showForm.value = false
    await fetchSkills()
  }
}

async function handleDelete() {
  const { success } = await del(`/admin/skills/${deleteId.value}`)
  if (success) {
    uiStore.success('Skill deleted.')
    deleteId.value = null
    await fetchSkills()
  }
}

onMounted(fetchSkills)
</script>
<template>
  <AdminLayout>
    <PageHeader title="Skills" subtitle="Manage your technical skills">
      <template #action>
        <button @click="openCreate"
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                 text-white px-4 py-2.5 rounded-xl text-sm font-medium
                 transition-colors">
            <PlusIcon class="w-4 h-4" /> Add Skill
        </button>
      </template>
    </PageHeader>

    <!-- Inline form -->
     <div v-if="showForm"
      class="bg-slate-800 rounded-2xl border border-slate-700 p-6 mb-6">
        <h3 class="text-white font-semibold mb-4">
          {{ editId ? 'Edit Skill' : 'New Skill' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
          <FormInput label="Name" v-model="form.name"
            placeholder="e.g. Laravel" :required="true" />
          <FormInput label="Category" v-model="form.category"
            placeholder="e.g. Backend" />
          <FormInput label="Icon class" v-model="form.icon"
            placeholder="e.g. devicon-laravel-plain" />
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">
              Proficiency: {{ form.proficiency }}%
            </label>
            <input type="range" v-model="form.proficiency"
              min="0" max="100"
              class="w-full accent-indigo-600" />
          </div>
          <FormInput label="Order" v-model="form.order" type="number" />
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

    <!-- Skills Grid-->
     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="skill in skills" :key="skill.id"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-white font-medium">{{ skill.name }}</p>
            <p class="text-slate-400 text-xs mt-0.5">{{ skill.category }}</p>
          </div>
          <dv class="flex gap-1">
            <button @click="openEdit(skill)"
              class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-700
                     rounded-lg transition-colors text-xs">
              Edit
            </button>
            <button @click="deleteId = skill.id"
              class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-500/10
                     rounded-lg transition-colors">
              <TrashIcon class="w-4 h-4" />
            </button>
          </dv>
        </div>
        <div class="w-full bg-slate-700 rounded-full h-1.5">
          <div
            class="bg-indigo-500 h-1.5 rounded-full transition-all"
            :style="{ width: skill.proficiency + '%' }"
          />
        </div>
        <p class="text-slate-400 text-xs mt-1 text-right">
          {{ skill.proficiency }}%
        </p>
      </div>
     </div>

     <ConfirmModal
        :show="!!deleteId"
        title="Delete skill?"
        message="This skill will be permanetly removed."
        @confirm="handleDelete"
        @cancel="deleteId = null"
      />
  </AdminLayout>
</template>
