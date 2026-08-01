<script setup>
import { ArrowTopRightOnSquareIcon, CodeBracketIcon } from '@heroicons/vue/24/outline';

defineProps({
  project: { type: Object, required: true }
})
</script>

<template>
  <div class="rounded-2xl overflow-hidden border transition-all group dark:bg-slate-900 dark:border-slate-800
             dark:hover:border-indigo-500/40 bg-[#0B2B26] border-[#1A4A42] hover:border-[#00F0A0]/40">

    <!-- Thumbnail-->
     <div class="aspect-video overflow-hidden dark:bg-slate-800 bg-[#0D3530]">
      <img
        v-if="project.thumbnail"
        :src="project.thumbnail"
        :alt="project.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <div v-else
        class="w-full h-full flex items-center justify-center text-sm
                dark:text-slate-600 text-[#1A4A42]">
        No preview
      </div>
     </div>

     <!-- Body -->
      <div class="p-5">

        <!-- Category + featured -->
         <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider
                      dark:text-indigo-400 text-[#00F0A0]">
            {{ project.category || 'Project' }}
          </span>
          <span v-if="project.featured"
            class="px-2 py-0.5 text-xs rounded-full font-medium border dark:bg-indigo-500/10 dark:text-indigo-400
                  dark:border-indigo-500/20 bg-[#00F0A0]/10 text-[#00F0A0] border-[#00F0A0]/30">
            Featured
          </span>
         </div>

         <h3 class="font-semibold text-lg mb-2 dark:text-white text-[#00F0A0]">{{ project.title }}</h3>
         <p class="text-sm leading-relaxed mb-4 line-clamp-2 dark:text-slate-400 text-[#B2DFDB]">
          {{ project.description }}
         </p>

         <!-- Tech stack -->
          <div class="flex flex-wrap gap-2 mb-5">
            <span
              v-for="tech in (project.tech_stack ?? []).slice(0, 4)"
              :key="tech"
              class="px-2.5 py-1 text-xs rounded-lg border dark:bg-slate-800 dark:text-slate-300
                    dark:border-slate-700 bg-[#1A4A42] text-[#B2DFDB] border-[#1A4A42]">
              {{ tech }}
            </span>
            <span
              v-if="(project.tech_stack ?? []).length > 4"
              class="px-2.5 py-1 text-xs rounded-lg border dark:bg-slate-800 dark:text-slate-400
                    dark:border-slate-700 bg-[#1A4A42] text-[#7BB8B2] border-[#1A4A42]">
              +{{ project.tech_stack.length -4  }} more
            </span>
          </div>

          <!-- Links -->
           <div class="flex gap-3">
            <a
              v-if="project.live_url"
              :href="project.live_url"
              target="_blank"
              class="flex items-center gap-1.5 px-4 py-2 text-xs font-medium rounded-xl transition-colors
                    dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
              <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
              Live Demo
            </a>
            <a
              v-if="project.github_url"
              :href="project.github_url"
              target="_blank"
              class="flex items-center gap-1.5 px-4 py-2 text-xs font-medium rounded-xl transition-colors border dark:border-slate-700 dark:hover:border-slate-500
                    dark:text-slate-300 dark:hover:text-white border-[#1A4A42] hover:border-[#00F0A0]/50 text-[#B2DFDB] hover:text-[#00F0A0]">
              <CodeBracketIcon class="w-3.5 h-3.5" />
              GitHub
            </a>
            <router-link
              :to="'/projects/' + project.slug"
              class="flex items-center gap-1.5 px-4 py-2 text-xs font-medium rounded-xl transition-colors border ml-auto dark:border-slate-700 dark:hover:border-slate-500
                    dark:text-slate-300 dark:hover:text-white border-[#1A4A42] hover:border-[#00F0A0]/50 text-[#B2DFDB] hover:text-[#00F0A0]">
              Details →
            </router-link>
           </div>
      </div>
  </div>
</template>
