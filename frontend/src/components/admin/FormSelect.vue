<script setup>
defineProps({
  label:      { type: String, required: true },
  modelValue: { type: [String, Number], default: '' },
  options:    { type: Array,   default: () => [] },
  required:   { type: Boolean, default: false },
  error:      { type: String,  default: '' },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div>
    <label class="block text-sm font-dedium text-slate-300 mb-1">
      {{ label }}
      <span class="text-red-400 ml-0.5">*</span>
    </label>
    <select
      :value="modelValue"
      @change="emit('update:modelValue', $event.target.value)"
      :class="['w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5',
               'text-white focus:outline-none focus:ring-2 focus:ring-indigo-500',
               error ? 'border-red-500' : 'border-slate-600']">
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value">
          {{ option.label }}
        </option>
    </select>
    <p v-if="error" class="mt-1 text-xs text-red-400">{{ error }}</p>
  </div>
</template>
