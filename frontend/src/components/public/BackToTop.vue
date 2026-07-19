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
      class="fixed bottom-8 right-8 z-50 w-10 h-10 bg-indigo-600
          hover:bg-indigo-700 text-white rounded-xl shadow-lg
          flex items-center justify-center transition-colors"
      title="Back to top">
      <ArrowUpIcon class="w-5 h-5" />
    </button>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to        { opacity: 0; }
</style>
