<script setup>
defineProps({
  label:        { type: String, required: true },
  modelValue:   { type: String, default: '' },
  placeholder:  { type: String, default: '' },
  rows:         { type: Number, default: 4 },
  required:     { type: Boolean, default: false },
  error:        { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div>
    <label class="block text-sm font-medium text-slate-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-400 ml-0 5">*</span>
    </label>
    <textarea
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      @input="emit('update:modelValue', $event.target.value)"
      :class="['w-full bg-slate-900 border rounded-xl px-4 py-2.5 text-white',
                'placeholder-slate-500 focus:outline-none focus:ring-2 resize-none',
                'focus:ring-indigo-500 focus:border-transparent transition-colors',
                error ? 'border-red-500' : 'border-slate-600']" />
      <p v-if="error" class="mt-1 text-xs text-red-400">{{ error }}</p>
  </div>
</template>
