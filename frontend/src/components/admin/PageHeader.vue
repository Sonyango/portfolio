<script setup>
defineProps({
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 sm:mb-8">
    <div class="min-w-0">
      <h2 class="text-xl sm:text-2xl font-bold text-white truncate">{{ title }}</h2>
      <p v-if="subtitle" class="text-slate-400 mt-1 text-sm">{{ subtitle }}</p>
    </div>
    <div class="shrink-0">
      <slot name="action" />
    </div>
  </div>
</template>
