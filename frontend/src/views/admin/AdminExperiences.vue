<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue'
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import DatePicker from '@/components/admin/DatePicker.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { PlusIcon, PencilIcon, TrashIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';


const { get, post, put, del } = useApi()
const uiStore     = useUiStore()
const experiences = ref([])
const showForm    = ref(false)
const deleteId    = ref(null)
const editId      = ref(null)
const loading     = ref(false)

// Form state
const form = ref({
  company: '',
  role: '',
  description: '',
  start_date: '',
  end_date: '',
  current: false,
  location: '',
  order:    0,
})

// Track originals for dirty check
const originalForm = ref(null)

// Form validation
const isFormValid = computed(() => {
  return  form.value.company.trim().length > 0 &&
          form.value.role.trim().length > 0 &&
          form.value.start_date.length > 0 &&
          (form.value.current || form.value.end_date.length > 0)
})

const missingFields = computed(() => {
  const missing = []
  if (!form.value.company.trim()) missing.push('Company')
  if (!form.value.role.trim())    missing.push('Role')
  if (!form.value.start_date)     missing.push('Start date')
  if (!form.value.current && !form.value.end_date) {
    missing.push('End date (or check "Currently working here")')
  }
  return missing
})

// Dirty check for edit mode
const isFormDirty = computed(() => {
  if (!editId.value) return true
  if (!originalForm.value) return false
  return JSON.stringify(form.value) !== JSON.stringify(originalForm.value)
})

const canSubmit = computed(() => {
  return isFormValid.value && isFormDirty.value && !loading.value
})

// When end date is provided, disable current checkbox
watch(() => form.value.end_date, (endDate) => {
  if (endDate) {
    form.value.current = false
  }
})

// When current is checked, clear end date
watch(() => form.value.current, (isCureent) => {
  if (isCureent) {
    form.value.end_date = ''
  }
})

// Fetch experiences
async function fetchExperiences() {
  loading.value = true
  const { data } = await get('/admin/experiences')
  if (data) experiences.value = data.data ?? []
  loading.value = false
}

// Open create form
function openCreate() {
  editId.value  = null
  form.value    = {
    company: '',
    role: '',
    description: '',
    start_date: '',
    end_date: '',
    current: false,
    location: '',
    order:    0,
  }
  originalForm.value = null
  showForm.value = true
}

// Open edit and prepopulate all fields
function openEdit(exp) {
  editId.value    = exp.id
  form.value      = {
    company:      exp.company     || '',
    role:         exp.role        || '',
    description:  exp.description || '',
    // Use raw dates for form population
    start_date:   exp.start_date_raw  || '',
    end_date:     exp.current ? '' : (exp.end_date_raw || ''),
    current:      exp.current === true || exp.current === 1,
    location:     exp.location    || '',
    order:        exp.order       || 0,
  }
  // Save original for dirty tracking
  originalForm.value  = JSON.parse(JSON.stringify(form.value))
  showForm.value = true
}

// Form submission
async function handleSubmit() {
  if (!canSubmit.value) return

  const payload = {
    company:      form.value.company,
    role:         form.value.role,
    description:  form.value.description,
    start_date:   form.value.start_date,
    end_date:     form.value.current ? null : form.value.end_date,
    current:      form.value.current,
    location:     form.value.location,
    order:        form.value.order,
  }

  const result = editId.value
    ? await put(`/admin/experiences/${editId.value}`, payload)
    : await post('/admin/experiences', payload)

  if (result.success) {
    uiStore.success(editId.value
      ? 'Experience updated.'
      : 'Experience added.')
    showForm.value = false
    await fetchExperiences()
  }
}

async function handleDelete() {
  if (!deleteId.value) return
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
     <!-- <div v-if="showForm"
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
    </div> -->

    <teleport to="body" v-if="showForm">
      <!-- Overlay -->
       <div
          class="fixed inset-0 bg-black/60 z-40"
          @click="showForm = false" />

      <!-- Drawer -->
       <div class="fixed right-0 top-0 h-full w-full sm:max-w-2xl
                  bg-slate-800 border border-slate-700 z-50 overflow-y-auto">
        <!-- Header -->
         <div class="flex items-center justify-between px-6 py-4
                    border-b border-slate-700 sticky top-0 bg-slate-800 z-10">
            <h3 class="text-lg font-semibold text-white">
              {{ editId ? 'Edit Experience' : 'Add Experience' }}
            </h3>
            <button
              @click="showForm = false"
              class="p-2 text-slate-400 hover:text-white rounded-lg transition-colors">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Form body -->
           <div class="p-6 space-y-5">

            <!-- Company -->
             <FormInput
                label="Company"
                v-model="form.company"
                placeholder="e.g. Acme Corp"
                :required="true"
              />

            <!-- Role -->
             <FormInput
                label="Role / Position"
                v-model="form.role"
                placeholder="e.g. Full Stack Developer"
                :required="true"
              />

            <!-- Location -->
             <FormInput
                label="Location"
                v-model="form.location"
                placeholder="e.g. Nairobi, Kenya"
              />

            <!-- Dates grid -->
             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Start date -->
               <DatePicker
                  label="Start Date"
                  v-model="form.start_date"
                  :required="true"
                  placeholder="Pick start date"
                  :max-date="form.end_date || ''"
                />

              <!-- End date -->
               <DatePicker
                  label="End Date"
                  v-model="form.end_date"
                  placeholder="Pick end date"
                  :disabled="form.current"
                  :min-date="form.start_date || ''"
                />
             </div>

             <!-- Currently working here checkbox -->
              <div
                class="flex items-start gap-3 p-4 rounded-xl border transition-colors"
                :class="form.end_date
                  ? 'bg-slate-800 border-slate-700 opacity-60'
                  : 'bg-slate-900/50 border-slate-700 hover:border-indigo-500/30'">
                  <input
                    type="checkbox"
                    id="current"
                    v-model="form.current"
                    :disabled="!!form.end_date"
                    class="w-4 h-4 mt-0.5 accent-indigo-600"
                    :class="form.end_date ? 'cursor-not-allowed' : 'cursor-pointer'"
                  />
                  <div>
                    <label
                      for="current"
                      class="text-sm font-medium transiton-colors"
                      :class="form.end_date
                        ? 'text-slate-500 cursor-not-allowed'
                        : 'text-slate-300 cursor-pointer'">
                        Currently working here
                    </label>
                    <p class="text-xs mt-0.5"
                        :class="form.end_date ? 'text-slate-600' : 'text-slate-500'">
                        <span v-if="form.end_date">
                          Clear the end date to enable this option.
                        </span>
                        <span v-else>
                          Check this if you still work here, end date will be shown as "Present"
                        </span>
                    </p>
                  </div>
              </div>
              <!-- Description -->
               <FormTextarea
                  label="Description"
                  v-model="form.description"
                  placeholder="Responsibilities and achievements..."
                  :rows="4"
                />

              <!-- Order -->
               <FormInput
                  label="Display Order"
                  v-model="form.order"
                  type="number"
                  placeholder="0"
                />

              <!-- Validation hint -->
               <div
                  v-if="!isFormValid && missingFields.length > 0"
                  class="px-4 py-3 bg-amber-500/10 border border-amber-500/20
                        rounded-xl text-amber-400 text-sm">
                  <p class="font-medium mb-1">Required before saving:</p>
                  <ul class="text-xs space-y-0.5 list-disc list-inside">
                    <li v-for="field in missingFields" :key="field">
                      {{ field }}
                    </li>
                  </ul>
                </div>
           </div>

           <!-- Footer -->
            <div class="sticky bottom-0 px-6 py-4 border-t border-slate-700
                      bg-slate-800 flex gap-3 justify-end">
              <button
                @click="showForm = false"
                class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                      hover:bg-slate-700 text-sm font-medium transition-colors">
                Cancel
              </button>

              <button
                @click="handleSubmit"
                :disabled="!canSubmit"
                :title="!isFormValid
                  ? 'Fill in: ' + missingFields.join(', ')
                  : editId && !isFormDirty
                    ? 'Make a change before updating'
                    : ''"
                :class="['px-6 py-2.5 rounded-xl text-sm font-medium transition-all',
                  canSubmit
                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer'
                    : 'bg-slate-600 text-slate-400 cursor-not-allowed opacity-60']">
                <span v-if="loading">Saving...</span>
                <span v-else>{{ editId ? 'Update Experience' : 'Add Experience' }}</span>
              </button>
            </div>
        </div>
    </teleport>

    <!-- Timeline-->
     <div class="space-y-4">
      <div v-if="experiences.length === 0 && !loading"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-12
               text-center text-slate-400">
        No experience entries yet.
      </div>

      <div
        v-for="exp in experiences"
        :key="exp.id"
        class="bg-slate-800 rounded-2xl border border-slate-700 p-4 sm:p-5
               flex items-start gap-3 sm:gap-4 hover:border-slate-600 transition-colors w-full">
          <!-- Timeline dot -->
        <div
          class="w-2 h-2 rounded-ful mt-1.5 shrink-0"
          :class="exp.current
            ? 'bg-green-500'
            : 'bg-indigo-500'"
        />
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2 sm:gap-4 w-full min-w-0">
            <div class="min-w-0 flex-1">
              <p class="text-white font-semibold truncate">{{ exp.role }}</p>
              <p class="text-indigo-400 text-sm truncate">{{ exp.company }}</p>
              <p class="text-slate-400 text-xs mt-1 flex-wrap gap-1">
                <span>{{ exp.start_date }} -</span>
                <span :class="exp.current ? 'text-green-400 font-medium' : ''">
                  {{ exp.current ? 'Present' : exp.end_date }}
                </span>
                <span v-if="exp.location">
                  &nbsp;.&nbsp; {{ exp.location }}
                </span>
              </p>
            </div>

            <div class="flex flex-col items-end gap-2 shrink-0">
              <!-- Current badge only when current it true -->
               <span
                  v-if="exp.current === true"
                  class="px-2 py-0.5 bg-green-500/10 text-green-400 text-xs
                        rounded-full border border-green-500/20 font-medium whitespace-nowrap">
                  Current
                </span>
                <div class="flex items-center gap-1">
                  <button @click="openEdit(exp)"
                    class="p-1.5 sm:p-2 text-slate-400 hover:text-white hover:bg-slate-700
                          rounded-lg transition-colors"
                    >
                    <PencilIcon class="w-4 h-4" />
                  </button>

                  <button @click="deleteId = exp.id"
                    class="p-1.5 sm:p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10
                          rounded-lg transition-colors"
                    >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>


            </div>
          </div>
          <p v-if="exp.description" class="text-slate-300 text-sm mt-2 leading-relaxed">
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
