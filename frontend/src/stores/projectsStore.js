import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/index.js';

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref([])
  const current  = ref(null)
  const loading  = ref(false)

  async function fetchProjects(category = null) {
    loading.value = true
    try {
      const params = category ? { category } : {}
      const { data } = await api.get('/projects', { params })
      projects.value = data.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function fetchProject(slug) {
    loading.value = true
    try {
      const { data } = await api.get(`/projects/${slug}`)
      current.value = data.data ?? null
    } finally {
      loading.value = false
    }
  }

  return { projects, current, loading, fetchProjects, fetchProject }
})
