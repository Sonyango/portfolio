<script setup>
import { onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PublicLayout from '@/components/public/PublicLayout.vue';
import { usePostsStore } from '@/stores/postsStore';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import DOMPurify from 'dompurify';

const route       = useRoute()
const router      = useRouter()
const postsStore  = usePostsStore()

watch(() => postsStore.current, (post) => {
  if (post) {
    document.title = `${post.title} | Stephen Portfolio`
  }
})

const safeContent = computed(() => {
  if (!postsStore.current?.content) return ''
  return DOMPurify.sanitize(postsStore.current.content)
})

onMounted(async () => {
  await postsStore.fetchPost(route.params.slug)
  if (!postsStore.current) {
    router.push({ name: 'blog' })
  }
})
</script>

<template>
  <PublicLayout>
    <article class="py-24 px-4">
      <div class="max-w-3xl mx-auto">

        <!-- Back -->
         <router-link to="/blog"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-10 transition-colors">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Blog
          </router-link>

          <div v-if="postsStore.loading" class="text-center text-slate-400 py-20">
            Loading posts...
          </div>

          <template v-else-if="postsStore.current">
            <!-- Categores -->
             <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="cat in postsStore.current.categories ?? []"
                :key="cat.id"
                class="text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                {{ cat.name }}
              </span>
             </div>

             <!-- Title -->
              <h1 class="font-display text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
                {{ postsStore.current.title }}
              </h1>

              <!-- Meta-->
               <div class="flex items-center gap-4 text-slate-400 text-sm mb-8 pb-8 border-b border-slate-800">
                <span>By {{ postsStore.current.author }}</span>
                <span>.</span>
                <span>{{ postsStore.current.published_at }}</span>
               </div>

               <!-- Thumbnail -->
                <div v-if="postsStore.current.thumbnail"
                  class="rounded-2xl overflow-hidden border border-slate-800 mb-10">
                  <img
                    :src="postsStore.current.thumbnail"
                    :alt="postsStore.current.title"
                    class="w-full object-cover" />
                </div>

                <!-- Content -->
                 <dv
                  class="prose prose-invert prose-slate max-w-none prose-heading:font-display prose-a:text-indigo-400 prose-code:text-indigo-300">
                  v-html="safeContent"
                 </dv>

                <!-- Tags -->
                <div v-if="postsStore.current.tags?.length > 0"
                    class="mt-12 pt-8 border-t border-slate-800">
                    <h3 class="text-white font-semibold mb-3 text-sm">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                      <router-link class="px-3 py-1.5 border border-slate-700 hover:border-indigo-500 text-slate-400 hover:text-indigo-400 text-xs rounded-full transition-colors">
                        #{{ tag.name }}
                      </router-link>
                    </div>
                </div>
          </template>
      </div>
    </article>
  </PublicLayout>
</template>
