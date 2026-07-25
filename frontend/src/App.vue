<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/authStore';
import { useSettingsStore } from '@/stores/settingsStore';
import { useDarkMode } from '@/composables/useDarkMode';


const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const { init }  = useDarkMode()

// Initialize dark mode immediately before anything renders
init()

onMounted(async () => {
  await settingsStore.fetchSettings(true)
  await authStore.fetchMe()
})
</script>

<template>
  <router-view />
</template>

<style scoped></style>
