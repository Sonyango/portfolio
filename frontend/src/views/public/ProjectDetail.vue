<script setup>
import { onMounted, watch } from 'vue';
import { useSeo } from '@/composables/useSeo';
import { useRoute, useRouter } from 'vue-router';
import PublicLayout from '@/components/public/PublicLayout.vue';
import { useProjectsStore } from '@/stores/projectsStore';
import {
  ArrowLeftIcon,
  ArrowTopRightOnSquareIcon,
  CodeBracketIcon,
} from '@heroicons/vue/24/outline';

const route         = useRoute()
const router        = useRouter()
const projectsStore = useProjectsStore()

// Update meta tags when project loads
watch(() => projectsStore.current, (project) => {
  if (project) {
    document.title = `${project.title} | Stephen Portfolio`
  }
})
</script>

<template>
  <PublicLayout>
    <section class="py-24 px-4 dark:bg-slate-950 bg-[#0B2B26]">
      <div class="max-w-4xl mx-auto">

        <!-- Back -->
         <router-link to="/projects"
            class="inline-flex items-center gap-2 text-sm mb-10
                 transition-colors
                 dark:text-slate-400 dark:hover:text-white
                 text-[#B2DFDB] hover:text-[#00F0A0]">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Projects
          </router-link>

          <div v-if="projectsStore.loading" class="text-center dark:text-slate-400 text-[#B2DFDB] py-20">
            Loading...
          </div>

          <template v-else-if="projectsStore.current">
            <!-- Header -->
             <div class="mb-8">
              <span class="text-sm font-semibold uppercase tracking-wider
                       dark:text-indigo-400 text-[#00F0A0]">
                {{ projectsStore.current.category || 'Project' }}
              </span>
              <h1 class="font-display text-4xl md:text-5xl font-bold
                     mt-2 mb-4
                     dark:text-white text-[#00F0A0]">
                {{ projectsStore.current.title }}
              </h1>
              <p class="text-lg leading-relaxed mb-8
                    dark:text-slate-400 text-[#B2DFDB]">
                {{ projectsStore.current.description }}
              </p>
             </div>

             <!-- Action buttons -->
              <div class="flex gap-3 mb-10">
                <a v-if="projectsStore.current.live_url"
                  :href="projectsStore.current.live_url"
                  target="_blank"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-xl
                     text-sm font-semibold transition-colors
                     dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white
                     bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
                  <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                  Live Demo
                </a>
                <a v-if="projectsStore.current.github_url"
                    :href="projectsStore.current.github_url"
                    target="_blank"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl
                     text-sm font-medium transition-colors border
                     dark:border-slate-700 dark:hover:border-slate-500
                     dark:text-slate-300 dark:hover:text-white
                     border-[#1A4A42] hover:border-[#00F0A0]/50
                     text-[#B2DFDB] hover:text-[#00F0A0]">
                    <CodeBracketIcon class="w-4 h-4" />
                    View Code
                </a>
              </div>

              <!-- Thumbnail -->
               <div v-if="projectsStore.current.thumbnail"
                  class="rounded-2xl overflow-hidden border mb-10
                   dark:border-slate-800 border-[#1A4A42]">
                <img
                  :src="projectsStore.current.thumbnail"
                  :alt="projectsStore.current.title"
                  class="w-full object-cover" />
               </div>

               <!-- Tech stack -->
                <div class="mb-10">
                  <h2 class="font-semibold mb-3
                       dark:text-white text-[#00F0A0]">Tech Stack</h2>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="tech in projectsStore.current.tech_stack ?? []"
                      :key="tech"
                      class="px-3 py-1.5 rounded-xl text-sm border
                       dark:bg-slate-800 dark:border-slate-700
                       dark:text-slate-300
                       bg-[#1A4A42] border-[#1A4A42] text-[#B2DFDB]">
                      {{ tech }}
                    </span>
                  </div>
                </div>

                <!-- Content -->
                 <div v-if="projectsStore.current.content"
                    class="prose max-w-none
                    dark:prose-invert dark:prose-slate
                    prose-headings:text-[#00F0A0]
                    prose-p:text-[#B2DFDB]
                    prose-a:text-[#00F0A0]
                    prose-strong:text-[#B2DFDB]
                    prose-code:text-[#00F0A0]
                    dark:prose-headings:text-white
                    dark:prose-p:text-slate-300
                    dark:prose-a:text-indigo-400
                    dark:prose-code:text-indigo-300">
                    <div v-html="projectsStore.current.content" />
                  </div>

                <!-- Image gallery -->
                 <div v-if="projectsStore.current.images?.length > 0" class="mt-12">
                  <h2 class="font-semibold mb-4
                       dark:text-white text-[#00F0A0]">Gallery</h2>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                      v-for="image in projectsStore.current.images"
                      :key="image.id"
                      class="rounded-xl overflow-hidden border
                       dark:border-slate-800 border-[#1A4A42]">
                      <image
                          :src="image.image_url"
                          :alt="image.caption || projectsStore.current.title"
                          class="w-full object-cover"
                          loading="lazy" />
                      <p v-if="image.caption" class="px-4 py-2 dark:text-slate-400 text-xs text-[#7BB8B2]">
                        {{ image.caption }}
                      </p>
                    </div>
                  </div>
                 </div>
          </template>

      </div>
    </section>
  </PublicLayout>
</template>
