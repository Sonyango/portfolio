<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PublicLayout from '@/components/public/PublicLayout.vue';
import PostCard from '@/components/public/PostCard.vue';
import { usePostsStore } from '@/stores/postsStore';
import api from '@/api/index.js';
import { useSeo } from '@/composables/useSeo';

useSeo({
  title:        'Blog',
  description:  'Artcles on webdevelopment, ICT, and software engineering.',
  url:          window.location.href,
})

const route         = useRoute()
const router        = useRouter()
const postsStore    = usePostsStore()
const categories    = ref([])
const tags          = ref([])

async function fetchFilters() {
  const [catRes, tagRes] = await Promise.all([
    api.get('/categories'),
    api.get('/tags'),
  ])
  categories.value  = catRes.data.data ?? []
  tags.value        = tagRes.data.data ?? []
}

function applyFilter(type, value) {
  router.push({ query: { [type]: value, page: 1 }})
}

function clearFilters() {
  router.push({ query: {} })
}

async function load() {
  await postsStore.fetchPosts({
    page:     route.query.page      || 1,
    category: route.query.category  || undefined,
    tag:      route.query.tag       || undefined,
  })
}

watch(() => route.query, load, { immediate: true })
onMounted(fetchFilters)
</script>

<template>
  <PublicLayout>
    <section class="py-24 px-4">
      <div class="max-w-6xl mx-auto">

        <!-- Header -->
         <div class="text-center mb-16">
          <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
            Thoughts & Tutorials
          </p>
          <h1 class="font-display text-5xl font-bold text-white mb-4">Blog</h1>
          <p class="text-slate-400 max-w-xl mx-auto">
            Articles on web development, ICT, and software engineering.
          </p>
         </div>

         <div class="flex gap-8">
          <!-- post grid -->
           <div class="flex-1">
            <!-- Active filter banner -->
             <div v-if="route.query.category || route.query.tag"
                class="flex items-center gap-3 mb-6 px-4 py-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-sm">
                <span class="text-indigo-400">
                  Filtering by:
                  <strong>{{ route.query.category || route.query.tag }}</strong>
                </span>
                <button @click="clearFilters"
                  class="ml-auto text-slate-400 hover:text-white text-xs">
                  Clear
                </button>
              </div>

              <div v-if="postsStore.loading" class="text-center text-slate-400 py-20">
                Loading posts...
              </div>

              <div v-else-if="postsStore.posts.length === 0" class="text-center text-slate-400 py-20">
                No posts found.
              </div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <PostCard
                  v-for="post in postsStore.posts"
                  :key="post.id"
                  :post="post"
                />
              </div>

              <!-- Pagination -->
               <div v-if="postsStore.meta?.last_page > 1" class="flex justify-center gap-2 mt-12">
                <button
                  v-for="page in postsStore.meta.last_page"
                  :key="page"
                  @click="router.push({ query: { ...route.query, page } })"
                  :class="['w-10 h-10 rounded-xl text-sm font-medium transition-colors',
                  Number(route.query.page || 1) === page
                    ? 'bg-indigo-600 text-white'
                    : 'border border-slate-700 text-slate-400 hover:text-white']">
                    {{ page }}
                </button>
               </div>
           </div>

           <!-- Sidebar -->
            <div class="hidden lg:block w-64 shrink-0 space-y-6">
              <!-- Categories -->
               <div v-if="categories.length > 0" class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-white font-semibold mb-3 text-sm">Categories</h3>
                <div class="space-y-1">
                  <button
                      v-for="cat in categories"
                      :key="cat.id"
                      @click="applyFilter('category', cat.slug)"
                      :class="['w-full text-left px-3 py-2 rounded-xl text-sm transition-colors',
                    route.query.category === cat.slug
                      ? 'bg-indigo-600 text-white'
                      : 'text-slate-400 hover:text-white hover:bg-slate-800']">
                      {{ cat.name }}
                  </button>
                </div>
               </div>

               <!-- Tags -->
                <div v-if="tags.length > 0" class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                  <h3 class="text-white font-semibold mb-3 text-sm">Tags</h3>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="tag in tags"
                      :key="tag.id"
                      @click="applyFilter('tag', tag.slug)"
                      :class="['px-3 py-1.5 rounded-full text-xs font-medium transition-colors',
                    route.query.tag === tag.slug
                      ? 'bg-indigo-600 text-white'
                      : 'border border-slate-700 text-slate-400 hover:text-white']">
                      #{{ tag.name }}
                    </button>
                  </div>
                </div>

            </div>
         </div>
      </div>
    </section>
  </PublicLayout>
</template>
