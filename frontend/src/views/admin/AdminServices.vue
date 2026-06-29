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
const uiStore   = useUiStore()
const services  = ref([])
const showForm  = ref(false)
const deleteId  = ref(null)
const editId    = ref(null)

const form = ref({
  title: '', description: '', icon: '',
  price_range: '', order: 0, published: true
})

async function fetchServices() {
  const { data } = await get('/admin/services')
  if (data) services.value = data.data
}

function openCreate() {
  editId.value = null
  form.value   = {
    title: '', description: '', icon: '',
    price_range: '', order: 0, published: true
  }
  showForm.value  = true
}

function openEdit(service) {
  editId.value    = service.id
  form.value      = { ...service }
  showForm.value  = true
}

async function handleSubmit() {
  const result = editId.value
    ? await put(`/admin/services/${editId.value}`, form.value)
    : await post('/admin/services', form.value)

  if (result.success) {
    uiStore.success(editId.value ? 'Service updated.' : 'Service added.')
    showForm.value = false
    await fetchServices()
  }
}

async function handleDelete() {
  const { success } = await del(`/admin/services/${deleteId.value}`)
  if (success) {
    uiStore.success('Service deleted.')
    deleteId.value  = null
    await fetchServices()
  }
}

onMounted(fetchServices)
</script>
<template>
  <AdminLayout>
    <PageHeader title="Services" subtitle="Manage your freelance service offerings">
      <template #action>
        <button @click="openCreate"
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                 text-white px-4 py-2.5 rounded-xl text-sm font-medium
                 transition-colors">
          <PlusIcon class="w-4 h-4" /> Add Service
        </button>
      </template>
    </PageHeader>

    <!--Form-->
    <div v-if="showForm"
      class="bg-slate-800 rounded-2xl border border-slate-700 p-6 mb-6">
      <h3 class="text-white font-semibold mb-4">
        {{ editId ? 'Edit Service' : 'New Service' }}
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <FormInput label="Title" v-model="form.title"
          placeholder="Web development" :required="true" />
        <FormInput label="Icon" v-model="form.icon"
          placeholder="e.g. code-bracket" />
        <FormInput label="Price Range" v-model="form.price_range"
          placeholder="e.g. KES 50k - 150k" />
        <FormInput label="Order" v-model="form.order" type="number" />
      </div>
      <div class="mt-4">
        <FormTextarea label="Description" v-model="form.description"
          placeholder="What does this service include?" :rows="3" :required="true" />
      </div>
      <div class="mt-4 flex items-center gap-2">
        <input type="checkbox" v-model="form.published" id="pub"
          class="w-4 h-4 accent-indigo-600" />
        <label for="pub" class="text-sm text-slate-300">Published</label>
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

    <!--Services grid-->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-if="services.length === 0"
        class="col-span-2 bg-slate-800 rounded-2xl border border-slate-700
               p-12 text-center text-slate-400">
        No services yet.
      </div>

      <div v-for="service in services" :key="service.id"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
        <div class="flex items-start justify-between mb-2">
          <div>
            <p class="text-white font-semibold">{{ service.title }}</p>
            <p v-if="service.price_range" class="text-indigo-400 text-sm mt-0.5">{{ service.price_range }}</p>
          </div>
          <div class="flex gap-2">
            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
              service.published
                ? 'bg-green-500/10 text-green-400'
                : 'bg-slate-600/50 text-slate-400']">
              {{ service.published ? 'Live' : 'Hidden' }}
            </span>
          </div>
        </div>
        <p class="text-slate-400 text-sm">{{ service.description }}</p>
        <div class="flex gap-2 mt-3">
          <button @click="openEdit(service)"
            class="flex items-center gap-1 px-3 py-1.5 text-slate-400
                   hover:text-white hover:bg-slate-700 rounded-lg transition-colors
                   text-xs">
            <PencilIcon class="w-3.5 h-3.5" /> Edit
          </button>
          <button @click="deleteId = service.id"
            class="flex items-center gap-1 px-3 py-1.5 text-slate-400
                   hover:text-red-400 hover:bg-red-500/10 rounded-lg
                   transition-colors text-xs">
            <TrashIcon class="w-3.5 h-3.5" /> Delete
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal
      :show="!!deleteId"
      title="Delete service?"
      message="This service will be permanently removed."
      @confirm="handleDelete"
      @cancel="deleteId = null"
    />
  </AdminLayout>
</template>
