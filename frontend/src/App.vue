<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/index.js'

const status = ref('checking...')

onMounted(async () => {
  const res = await api.get('/health')
  status.value = res.data.status
})
</script>

<template>
  <div class="p-8">
  <h1 class="text-2xl font-bold">Portfolio</h1>
  <p>Backend status: <strong>{{ status }}</strong></p>
  </div>
</template>

<style scoped></style>
