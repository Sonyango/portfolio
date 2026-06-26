<script setup>
defineProps({
  label:        { type: String, required: true },
  modelValue:   { type: [String, Number], default: '' },
  type:         { type: String, default: 'text' },
  placeholder:  { type: String, default: '' },
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

    <input
      :type="text"
      :value="modelValue"
      :placeholder="placeholder"
      @input="emit('update:modelValue', $event.target.value)"
      :class="['w-full bg-slate-900 border rounded-xl px-4 py-2.5 text-white',
                'placeholder-slate-500 focus:outline-none focus:ring-2',
                'focus:ring-indigo-500 focus:border-transparent transition-colors',
                error ? 'border-red-500'  : 'border-slate-600']" />
      <p v-if="error" class="mt-1 text-xs text-red-400">{{ error }}</p>
  </div>
</template>
