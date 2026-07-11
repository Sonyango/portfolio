<script setup>
import { ref, computed, onMounted } from 'vue';
import PublicLayout from '@/components/public/PublicLayout.vue';
import ProjectCard from '@/components/public/ProjectCard.vue';
import { useProjectsStore } from '@/stores/projectsStore';

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
    <section class="py-24 px-4">
      <div class="max-w-6xl mx-auto">

        <!-- header -->
         <div class="text-center mb-16">
          <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
            My Work
          </p>
          <h1 class="font-display text-5xl font-bold text-white mb-4">
            Projects
          </h1>
          <p class="text-slate-400 max-w-xl mx-auto">
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
                ? 'bg-indigo-600 text-white'
                : 'border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500']">
                {{ cat }}
            </button>
          </div>

        <!-- Loadng -->
         <div v-if="projectsStore.loading" class="text-center text-slate-400 py-20">
          Loading projects...
        </div>

        <!-- Empty -->
         <div v-else-if="filtered.length === 0"
          class="text-center text-slate-400 py-20">
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
