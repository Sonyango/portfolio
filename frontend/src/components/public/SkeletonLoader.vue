<script setup>
defineProps({
  lines:  { type: Number, default: 3 },
  height: { type: String, default: 'h-4' },
})
</script>

<template>
  <div class="animate-pulse space-y-3">
    <div
      v-for="i in lines"
      :key="i"
      :class="['bg-slate-800 rounded-lg', height,
                i === lines ? 'w-3/4' : 'w-full']" />
  </div>
</template>
