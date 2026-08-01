<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSeo } from '@/composables/useSeo';
import PublicLayout from '@/components/public/PublicLayout.vue';
import ProjectCard from '@/components/public/ProjectCard.vue';
import { useProjectsStore } from '@/stores/projectsStore';

useSeo({
  title:        'Projects',
  description:  'A showcase of my web development and ICT projects.',
  url:          window.location.href,
})

const projectsStore = useProjectsStore()
const activeFilter  = ref('all')

const categories  = computed(() => {
  const cats  = projectsStore.projects.map(p => p.category)
        .filter(Boolean)
  return ['all', ...new Set(cats)]
})

const filtered  = computed(() => {
  if (activeFilter.value === 'all') return projectsStore.projects
  return projectsStore.projects.filter(
    p => p.category === activeFilter.value
  )
})

onMounted(() => projectsStore.fetchProjects())
</script>

<template>
  <PublicLayout>
    <section class="py-24 px-4 dark:bg-slate-950 bg-[#0B2B26]">
      <div class="max-w-6xl mx-auto">

        <!-- header -->
         <div class="text-center mb-16">
          <p class="text-sm font-semibold tracking-widest uppercase mb-3
                    dark:text-indigo-400 text-[#00F0A0]">
            My Work
          </p>
          <h1 class="font-display text-5xl font-bold mb-4
                     dark:text-white text-[#00F0A0]">
            Projects
          </h1>
          <p class="max-w-xl mx-auto
                    dark:text-slate-400 text-[#B2DFDB]">
            A collection of things I've built from web apps to ICT solutions.
          </p>
         </div>

         <!-- Filter tabs -->
          <div class="flex flex-wrap justify-center gap-2 mb-12">
            <button
              v-for="cat in categories"
              :key="cat"
              @click="activeFilter = cat"
              :class="['px-5 py-2 rounded-full text-sm font-medium transition-colors capitalize',
              activeFilter === cat
                ? 'dark:bg-indigo-600 dark:text-white bg-[#00F0A0] text-[#0B2B26]'
                : 'border dark:border-slate-700 dark:text-slate-400 dark:hover:text-white dark:hover:border-slate-500 border-[#1A4A42] text-[#B2DFDB] hover:text-[#00F0A0] hover:border-[#00F0A0]/50']">
                {{ cat }}
            </button>
          </div>

        <!-- Loadng -->
         <div v-if="projectsStore.loading" class="text-center dark:text-slate-400 text-[#B2DFDB] py-20">
          Loading projects...
        </div>

        <!-- Empty -->
         <div v-else-if="filtered.length === 0"
          class="text-center dark:text-slate-400 text-[#B2DFDB] py-20">
          No projects found.
        </div>

        <!-- Grid -->
         <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <ProjectCard v-for="project in filtered"
            :key="project.id"
            :project="project"
           />
         </div>

      </div>
    </section>
  </PublicLayout>
</template>
