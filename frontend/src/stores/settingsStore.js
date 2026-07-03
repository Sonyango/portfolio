import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/index.js';

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref({});
  const loaded   = ref(false);

  async function fetchSettings() {
    if (loaded.value) return
    try {
      const { data } = await api.get('/settings')
      settings.value = data.data ?? {}
      loaded.value = true
    } catch {
      settings.value = {}
    }
  }

  function get(key, fallback = '') {
    return settings.value[key] ?? fallback
  }

  return { settings, loaded, fetchSettings, get }
})
