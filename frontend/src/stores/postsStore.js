import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/index.js';

export const usePostsStore = defineStore('posts', () => {
  const posts = ref([])
  const current = ref(null)
  const meta    = ref({})
  const loading = ref(false)

  async function fetchPosts(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/posts', { params })
      posts.value = data.data ?? []
      meta.value  = data.meta ?? {}
    } finally {
      loading.value = false
    }
  }

  async function fetchPost(slug) {
    loading.value = true
    try {
      const { data } = await api.get(`/posts/${slug }`)
      current.value = data.data ?? null
    } finally {
      loading.value = false
    }
  }

  return { posts, current, meta, loading, fetchPosts, fetchPost }
})
