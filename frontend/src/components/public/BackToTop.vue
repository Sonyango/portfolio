<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ArrowUpIcon } from '@heroicons/vue/24/outline';

const visible = ref(false)

function onScroll() {
  visible.value = window.scrollY > 400
}

function scrollToTop() {
  window.scrollToTop({ top: 0, behavior: 'smooth' })
}

onMounted(() => window.addEventListener('scroll', onScroll))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <transition name="fade">
    <button
      v-if="visible"
      @click="scrollToTop"
      class="fixed bottom-8 right-8 z-50 w-10 h-10 rounded-xl shadow-lg
             flex items-center justify-center transition-colors
             dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white
             bg-[#00F0A0] hover:bg-white text-[#0B2B26]
             border dark:border-transparent border-[#00F0A0]/50"
      title="Back to top">
      <ArrowUpIcon class="w-5 h-5" />
    </button>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to        { opacity: 0; }
</style>
