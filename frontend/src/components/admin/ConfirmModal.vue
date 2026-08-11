<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
  show:     { type: Boolean, default: false },
  title:    { type: String, default: 'Delete this item?' },
  message:  { type: String, default: 'This action cannot be undone.' },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <teleport to="body">
    <div v-if="show"
      class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center px-4">
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6 w-full max-w-md">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-400" />
          </div>
          <div>
            <h3 class="text-white font-semibold">{{ title }}</h3>
            <p class="text-slate-400 text-sm">{{ message }}</p>
          </div>
        </div>

        <div class="flex gap-3 justify-end">
          <button
          @click="emit('cancel')"
            class="px-4 py-2 rounded-xl border border-slate-600 text-slate-300
                    hover:bg-slate-700 text-sm font-medium transition-colors">
            Cancel
          </button>

          <button
            @click="emit('confirm')"
            class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700
                       text-white text-sm font-medium transition-colors">
            Delete
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>
