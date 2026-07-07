<script setup>
import { ArrowTopRightOnSquareIcon, CodeBracketIcon } from '@heroicons/vue/24/outline';

defineProps({
  project: { type: Object, required: true }
})
</script>

<template>
  <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden hover:border-indigo-500/40 transition-all group">

    <!-- Thumbnail-->
     <div class="aspect-video bg-slate-800 overflow-hidden">
      <img
        v-if="project.thumbnail"
        :src="project.thumbnail"
        :alt="project.title"
        class="w-full h-full object-cover transition-transform duration-500"
        loading="lazy"
      />
      <div v-else
        class="w-full h-full flex items-center justify-center text-slate-600 text-sm">
        No preview
      </div>
     </div>

     <!-- Body -->
      <div class="p-5">

        <!-- Category + featured -->
         <div class="flex items-center justify-between mb-2">
          <span class="text-indigo-400 text-xs font-semibold uppercase tracking-wider">
            {{ project.category || 'Project' }}
          </span>
          <span v-if="project.featured"
            class="px-2 py-0.5 bg-indigo-500/10 text-indigo-400 text-xs rounded-full font-medium">
            Featured
          </span>
         </div>

         <h3 class="text-white font-semibold text-lg mb-2">{{ project.title }}</h3>
         <p class="text-slate-400 text-sm leading-relaxed mb-4 line-clamp-2">
          {{ project.description }}
         </p>

         <!-- Tech stack -->
          <div class="flex flex-wrap gap-2 mb-5">
            <span
              v-for="tech in (project.tech_stack ?? []).slice(0, 4)"
              :key="tech"
              class="px-2.5 py-1 bg-slate-800 text-slate-300 text-xs rounded-lg border border-slate-700">
              {{ tech }}
            </span>
            <span
              v-if="(project.tech_stack ?? []).length > 4"
              class="px-2.5 py-1 bg-slate-800 text-slate-400 text-xs rounded-lg border border-slate-700">
              +{{ project.tech_stack.length -4  }} more
            </span>
          </div>

          <!-- Links -->
           <div class="flex gap-3">
            <a
              v-if="project.live_url"
              :href="project.live_url"
              target="_blank"
              class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-xl transition-colors">
              <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
              Live Demo
            </a>
            <a
              v-if="project.github_url"
              :href="project.github_url"
              target="_blank"
              class="flex items-center gap-1.5 px-4 py-2 bg-indigo-700 hover:border-slate-500 text-slate-300 hover:text-white text-xs font-medium rounded-xl transition-colors">
              <CodeBracketIcon class="w-3.5 h-3.5" />
              GitHub
            </a>
            <router-link
              :to="'/projects/' + project.slug"
              class="flex items-center gap-1.5 px-4 py-2 border border-slate-700 hover:border-slate-500 text-slate-300 hover:text-white text-xs font-medium rounded-xl transition-colors ml-auto">
              Details →
            </router-link>
           </div>
      </div>
  </div>
</template>
