<script setup>
import { ref, watch, computed } from 'vue';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label:      { type: String, default: 'Date' },
  required:   { type: Boolean, default: false },
  disabled:   { type: Boolean, default: false },
  error:      { type: String,  default: '' },
  maxDate:    { type: String,  default: '' },
  minDate:    { type: String,  default: '' },
  placeholder:{ type: String,  default: 'Pick a date...'},
})

const emit  = defineEmits(['update:modelValue'])

const inputRef      = ref(null)
const displayValue  = ref('')

// Format date value to readable display
function formatDisplay(val) {
  if (!val) return ''
  try {
    const date = new Date(val + 'T00:00:00')
    return date.toLocaleDateString('en-GB', {
      day:    '2-digit',
      month:  'short',
      year:   'numeric',
    })
  } catch {
    return val
  }
}

// Watch for external value changes (edit mode population)
watch(() => props.modelValue, (val) => {
  displayValue.value = formatDisplay(val)
}, { immediate: true })

// Open native date picker
function openPicker() {
  if (props.disabled) return
  inputRef.value?.showPicker?.()
  inputRef.value?.click()
}

// Handle picker selection
function onPickerChange(e) {
  const val = e.target.value
  if (!val) return
  displayValue.value = formatDisplay(val)
  emit('update:modelValue', val)
}

// Clear date
function clearDate(e) {
  e.stopPropagation()
  displayValue.value = ''
  emit('update:modelValue', '')
  if (inputRef.value) inputRef.value.value = ''
}
</script>

<template>
  <div>
    <label class="block text-sm font-medium mb-1"
            :class="disabled ? 'text-slate-500' : 'text-slate-300'">
      {{ label }}
      <span v-if="required" class="text-red-400 ml-0.5">*</span>
    </label>

    <div class="relative">
      <!-- Display button -->
       <button
          type="button"
          @click="openPicker"
          :disabled="disabled"
          :class="['w-full flex items-center justify-between px-4 py-2.5',
                    'border rounded-xl text-sm transition-colors',
                    'focus:outline-none focus:ring-2',
                    disabled
                    ? 'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed'
                    : error
                      ? 'bg-slate-900 border-red-500 focus:ring-red-500'
                      : 'bg-slate-900 border-slate-600 hover:border-indigo-500 focus:ring-indigo-500']">
          <span :class="displayValue && !disabled
            ? 'text-indigo-300'
            : 'text-slate-500'">
            {{ disabled ? 'N/A - Currently working here' : (displayValue || placeholder) }}
          </span>
          <div class="flex items-center gap-2">
            <button
              v-if="displayValue && !disabled"
              type="button"
              @click="clearDate"
              class="text-slate-400 hover:text-red-400 transition-colors text-lg leading-none"
              title="Clear date">
              x
            </button>
            <CalendarDaysIcon class="w-5 h-5 transition-colors"
              :class="disabled ? 'text-slate-600' : 'text-indigo-400'" />
          </div>
        </button>

        <!-- Hidden native date input -->
         <input
            ref="inputRef"
            type="date"
            :value="modelValue"
            :min="minDate"
            :max="maxDate"
            :disabled="disabled"
            class="admin-date-picker absolute opacity-0 pointer-events-none inset-0 w-full h-full"
            tabindex="-1"
            @change="onPickerChange"
          />
    </div>

    <!-- Selected date confirmation -->
     <p v-if="displayValue && !error && !disabled"
        class="mt-1 text-xs text-indigo-400 flex items-center gap-1">
        <span>✓</span> {{ displayValue }}
    </p>

    <p v-if="error && !disabled"
      class="mt-1 text-xs text-red-400">
      {{ error }}
    </p>
  </div>
</template>
