<script setup>
defineProps({
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="flex items-center justify-between mb-8">
    <div>
      <h2 class="text-2xl font-bold text-white">{{ title }}</h2>
      <p class="text-slate-400 mt-1 text-sm">{{ subtitle }}</p>
    </div>
    <slot name="action" />
  </div>
</template>
