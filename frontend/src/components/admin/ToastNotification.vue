<script setup>
import { useUiStore } from '@/stores/uiStore';
import {
  CheckCircleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
 } from '@heroicons/vue/24/outline';

 const uiStore = useUiStore()

 const icons = {
  success: CheckCircleIcon,
  error: XCircleIcon,
  info: InformationCircleIcon
 }

 const colors = {
  success: 'bg-green-500/10 border-green-500/30 text-green-400',
  error:    'bg-red-500/10 border-red-500/30 text-red-400',
  info:     'bg-blue-500/10 border-blue-500/30 text-blue-400',
 }
</script>

<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <transition-group name="toast">
      <div
        v-for="toast in uiStore.toasts"
        :key="toast.id"
        :class="['flex items-center gap-3 px-4 py-3 rounded-xl border text-sm min-w-64',
                colors[toast.type]
        ]">
        <component :is="icons[toast.type]" class="w-5 h-5 shrink-0" />
        <span class="flex-1">{{ toast.message }}</span>
        <button @click="uiStore.removeToast(toast.id)">
          <XMarkIcon class="w-4 h-4"  />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateX(100%); }
.toast-leave-to { opacity: 0; transform: translateX(100%);}
</style>
