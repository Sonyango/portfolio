<script setup>
import { ref, computed, watch } from 'vue';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: 'Publish Date & Time' },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  minDate: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const displayValue = ref(null)

// Format datetime-local value to readable display
function formatDisplay(val) {
  if (!val) return ''
  try {
    const date = new Date(val)
    return date.toLocaleDateString('en-GB', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    })
  } catch {
    return val
  }
}

// Minimum date - default to now
const minDateTime = computed(() => {
  if (props.minDate) return props.minDate
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

// When modelValue changes externally (e.g when editing a post)
watch(() => props.modelValue, (val) => {
  displayValue.value = formatDisplay(val)
}, { immediate: true })

// Open the native date picker programmatically
function openPicker() {
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

// Clear selection
function clearDate() {
  displayValue.value = ''
  emit('update:modelValue', '')
  if (inputRef.value) inputRef.value.value = ''
}
</script>

<template>
  <div>
    <label class="block text-sm font-medium text-slate-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-400 ml-0.5">*</span>
    </label>

    <!-- Custom display button -->
    <div class="relative">
      <button type="button" @click="openPicker" :class="['w-full flex items-center justify-between px-4 py-2.5',
        'bg-slate-900 border rounded-xl text-sm transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-indigo-500',
        error
          ? 'border-red-500'
          : 'border-slate-600 hover:border-indigo-500']">
        <span :class="displayValue ? 'text-indigo-300' : 'text-slate-500'">
          {{ displayValue || 'Pick date and time...' }}
        </span>
        <div class="flex items-center gap-2">
          <!-- Clear button -->
          <button v-if="displayValue" type="button" @click.stop="clearDate"
            class="text-slate-400 hover:text-red-400 transition-colors text-lg leading-none" title="Clear date">
            x
          </button>
          <CalendarDaysIcon class="w-5 h-5 text-indigo-400" />
        </div>
      </button>

      <!-- Hidden native input, only used to get the picker -->
      <input ref="inputRef" type="datetime-local" :value="modelValue" :min="minDateTime"
        class="admin-date-picker absolute opacity-0 pointer-events-none inset-0 w-full h-full" tabindex="-1"
        @change="onPickerChange" />
    </div>

    <!-- Selected date confirmation -->
    <p v-if="displayValue && !error" class="mt-1 text-xs text-indigo-400 flex items-center gap-1">
      <span>✓</span> Scheduled for {{ displayValue }}
    </p>

    <p v-if="error" class="mt-1 text-xs text-red-400">{{ error }}</p>
  </div>
</template>
