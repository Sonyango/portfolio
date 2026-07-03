<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from './stores/authStore';
import { useSettingsStore } from '@/stores/settingsStore';


const authStore = useAuthStore()
const settingsStore = useSettingsStore()

onMounted(async () => {
  await authStore.fetchMe()
  await settingsStore.fetchSettings()

})
</script>

<template>
  <router-view />
</template>

<style scoped></style>
