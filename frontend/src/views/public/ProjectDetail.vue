<script setup>
import { onMounted } from 'vue';
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
</script>

<template>
  <PublicLayout>
    <section class="py-24 px-4">
      <div class="max-w-4xl mx-auto">

        <!-- Back -->
         <router-link to="/projects"
            class="inline-flex items-center gap-2 text-slate-400
            hover:text-white text-sm mb-10 transition-colors">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Projects
          </router-link>

          <div v-if="projectsStore.loading" class="text-center text-slate-400 py-20">
            Loading...
          </div>

          <template v-else-if="projectsStore.current">
            <!-- Header -->
             <div class="mb-8">
              <span class="text-indigo-400 text-sm font-semibold uppercase tracking-wider">
                {{ projectsStore.current.category || 'Project' }}
              </span>
              <h1 class="font-display text-4xl md:text-5xl font-bold text-white mt-2 mb-4">
                {{ projectsStore.current.title }}
              </h1>
              <p class="text-slate-400 text-lg leading-relaxed">
                {{ projectsStore.current.description }}
              </p>
             </div>

             <!-- Action buttons -->
              <div class="flex gap-3 mb-10">
                <a v-if="projectsStore.current.live_url"
                  :href="projectsStore.current.live_url"
                  target="_blank"
                  class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600
                     hover:bg-indigo-700 text-white text-sm font-medium
                     rounded-xl transition-colors">
                  <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                  Live Demo
                </a>
                <a v-if="projectsStore.current.github_url"
                    :href="projectsStore.current.github_url"
                    target="_blank"
                    class="flex items-center gap-2 px-5 py-2.5 border border-slate-700
                     hover:border-slate-500 text-slate-300 hover:text-white
                     text-sm font-medium rounded-xl transition-colors">
                    <CodeBracketIcon class="w-4 h-4" />
                    View Code
                </a>
              </div>

              <!-- Thumbnail -->
               <div class="rounded-2xl overflow-hidden border border-slate-800 mb-10">
                <img
                  :src="projectsStore.current.thumbnail"
                  :alt="projectsStore.current.title"
                  class="w-full object-cover" />
               </div>

               <!-- Tech stack -->
                <div class="mb-10">
                  <h2 class="text-white font-semibold mb-3">Tech Stack</h2>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="tech in projectsStore.current.tech_stack ?? []"
                      :key="tech"
                      class="px-3 py-1.5 bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-xl">
                      {{ tech }}
                    </span>
                  </div>
                </div>

                <!-- Content -->
                 <div v-if="projectsStore.current.content"
                    class="prose prose-invert prose-slate max-w-none">
                    <div v-html="projectsStore.current.content" />
                  </div>

                <!-- Image gallery -->
                 <div v-if="projectsStore.current.images?.length > 0" class="mt-12">
                  <h2 class="text-white font-semibold mb-4">Gallery</h2>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                      v-for="image in projectsStore.current.images"
                      :key="image.id"
                      class="rounded-xl overflow-hidden border border-slate-800">
                      <image
                          :src="image.image_url"
                          :alt="image.caption || projectsStore.current.title"
                          class="w-full object-cover"
                          loading="lazy" />
                      <p v-if="image.caption" class="px-4 py-2 text-slate-400 text-xs">
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
