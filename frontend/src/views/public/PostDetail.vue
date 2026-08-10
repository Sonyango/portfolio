<script setup>
import { onMounted, computed, watch } from 'vue';
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
    <article class="py-24 px-4 dark:bg-slate-950 bg-[#0B2B26]">
      <div class="max-w-3xl mx-auto">

        <!-- Back -->
         <router-link to="/blog"
            class="inline-flex items-center gap-2 text-sm mb-10
                 transition-colors
                 dark:text-slate-400 dark:hover:text-white
                 text-[#B2DFDB] hover:text-[#00F0A0]">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Blog
          </router-link>

          <div v-if="postsStore.loading" class="text-center dark:text-slate-400 text-[#B2DFDB] py-20">
            Loading posts...
          </div>

          <template v-else-if="postsStore.current">
            <!-- Categores -->
             <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="cat in postsStore.current.categories ?? []"
                :key="cat.id"
                class="text-xs font-semibold uppercase tracking-wider
                     dark:text-indigo-400 text-[#00F0A0]">
                {{ cat.name }}
              </span>
             </div>

             <!-- Title -->
              <h1 class="font-display text-4xl md:text-5xl font-bold
                     mb-4 leading-tight
                     dark:text-white text-[#00F0A0]">
                {{ postsStore.current.title }}
              </h1>

              <!-- Meta-->
               <div class="flex items-center gap-4 text-sm mb-8 pb-8
                      border-b
                      dark:text-slate-400 dark:border-slate-800
                      text-[#7BB8B2] border-[#1A4A42]">
                <span>By {{ postsStore.current.author }}</span>
                <span class="dark:text-slate-600 text-[#1A4A42]">.</span>
                <span>{{ postsStore.current.published_at }}</span>
               </div>

               <!-- Thumbnail -->
                <div v-if="postsStore.current.thumbnail"
                  class="rounded-2xl overflow-hidden border mb-10
                   dark:border-slate-800 border-[#1A4A42]">
                  <img
                    :src="postsStore.current.thumbnail"
                    :alt="postsStore.current.title"
                    class="w-full object-cover" />
                </div>

                <!-- Content -->
                 <div
                  class="prose max-w-none
                   dark:prose-invert dark:prose-slate
                   prose-headings:text-[#00F0A0]
                   prose-p:text-[#B2DFDB]
                   prose-a:text-[#00F0A0]
                   prose-strong:text-[#B2DFDB]
                   prose-code:text-[#00F0A0]
                   prose-blockquote:border-[#00F0A0]/40
                   prose-blockquote:text-[#7BB8B2]
                   dark:prose-headings:text-white
                   dark:prose-p:text-slate-300
                   dark:prose-a:text-indigo-400
                   dark:prose-code:text-indigo-300">
                  v-html="safeContent"
                 </div>

                <!-- Tags -->
                <div v-if="postsStore.current.tags?.length > 0"
                    class="mt-12 pt-8 border-t dark:border-slate-800 border-[#1A4A42]">
                    <h3 class="font-semibold mb-3 text-sm
                       dark:text-white text-[#00F0A0]">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                      <router-link
                        v-for="tag in postsStore.current.tags"
                        :key="tag.id"
                        :to="'/blog?tag=' + tag.slug"
                        class="px-3 py-1.5 rounded-full text-xs transition-colors
                              border
                              dark:border-slate-700 dark:text-slate-400
                              dark:hover:border-indigo-500 dark:hover:text-indigo-400
                              border-[#1A4A42] text-[#B2DFDB]
                              hover:border-[#00F0A0]/50 hover:text-[#00F0A0]">
                        #{{ tag.name }}
                    </router-link>
                    </div>
                </div>
          </template>
      </div>
    </article>
  </PublicLayout>
</template>
