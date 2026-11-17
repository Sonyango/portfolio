<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSeo } from '@/composables/useSeo';
import PublicLayout from '@/components/public/PublicLayout.vue';
import ProjectCard from '@/components/public/ProjectCard.vue';
import { useProjectsStore } from '@/stores/projectsStore';

useSeo({
  title:        'Projects',
  description:  'A showcase of my web development and ICT projects.',
  url:          window.location.href,
})

const route = useRoute()
const router = useRouter()
const projectsStore = useProjectsStore()

const All = 'all'
const activeFilter  = ref(route.query.category || All)

/*One pass over the data gives both the category list and its count,
  so the number never disagrees with what the grid shows */

const categories  = computed(() => {
  // const cats  = projectsStore.projects.map(p => p.category)
  //       .filter(Boolean)
  // return ['all', ...new Set(cats)]
  const counts = {}
  for (const p of projectsStore.projects) {
    if (!p.category) continue
    counts[p.category] = (counts[p.category] || 0) + 1
  }

  const list = Object.keys(counts)
    .sort((a, b) => counts[b] - counts[a] || a.localeCompare(b))
    .map(name => ({ key: name, label: name, count: counts[name] }))

  return [
    { key: All, label: 'All Projects', count: projectsStore.projects.length },
    ...list,
  ]
})

const filtered  = computed(() => {
  if (activeFilter.value === All) return projectsStore.projects
  return projectsStore.projects.filter(
    p => p.category === activeFilter.value
  )
})

const activeLabel = computed(
  () => categories.value.find(c => c.key === activeFilter.value)?.label ?? 'All Projects'
)

/* Keeping filter in the URL allows users to bookmark or share a specific category view
  and the browser back button works as expected. */
watch(activeFilter, key => {
  router.replace({ query: key === All ? {} : { category: key } })
})

/* If a bookmarked category nolonger exists, fallback to everything. */
watch(categories, list => {
  if (!projectsStore.loading && !list.some(c => c.key === activeFilter.value)) {
    activeFilter.value = All
  }
})

onMounted(() => projectsStore.fetchProjects())
</script>

<template>
  <PublicLayout>
    <section class="py-6 px-4 sm:px-6 dark:bg-slate-950 bg-[#0B2B26]">
      <div class="mx-auto">

        <!-- Header: left-aligned so it lines up with the sidebar and grid below -->
         <header class="mb-6 lg:mb-10 max-w-2xl">
          <h1 class="font-display text-3xl font-bold mb-3
                     dark:text-white text-[#00F0A0]">
            Projects
          </h1>
          <p class="dark:text-slate-400 text-[#B2DFDB]">
            Collection of my projects.
          </p>
         </header>


          <div class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-10">
            <!-- Filter card -->
            <!-- lg:top-24 clears the site navbar; adjust to match its height. -->
             <aside arial-label="Project filters" class="lg:sticky lg:top-24 lg:self-start">
              <div class="rounded-2xl border p-2 lg:p-3 dark:bg-slate-900 dark:border-slate-800 bg-[#0F362F] border-[#1A4A42]">
                <p class="hidden lg:block px-3 pt-1 pb-2 text-sm font-medium dark:text-slate-400 text-[#B2DFDB]">
                  Browse by category
                </p>

                <!-- Mobile: horizontal scroller. Desktop: vertical list -->
                 <ul class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible [scrollbar-none] [&::-webkit-scrollbar]:hidden">

                  <li v-for="cat in categories" :key="cat.key" class="shrink-0 lg:shrink">
                    <button
                      type="button"
                      :aria-pressed="activeFilter === cat.key"
                      @click="activeFilter = cat.key"
                      :class="[
                        'relative flex w-full items-center justify-between gap-3 whitespace-nowrap',
                        'rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-colors',
                        'focus-visible:outline-2 focus-visible:outline-offset-2',
                        'dark:focus-visible:outline-indigo-400 focus-visible:outline-[#00F0A0]',
                        activeFilter === cat.key
                          ? 'dark:bg-indigo-600 dark:text-white bg-[#00F0A0] text-[#0B2B26]'
                          : 'dark:text-slate-300 dark:hover:bg-slate-800 text-[#B2DFDB] hover:bg-[#1A4A42]/60 hover:text-[#00F0A0]'
                      ]">
                      <span class="truncate">{{ cat.label }}</span>
                      <span :class="[
                        'min-w-6 rounded-full px-2 py-0.5 text-center text-xs font-semibold tabular-nums',
                        activeFilter === cat.key
                          ? 'dark:bg-indigo-400/30 dark:text-white bg-[#0B2B26]/15 text-[#0B2B26]'
                          : 'dark:bg-slate-800 dark:text-slate-300 bg-[#1A4A42] text-[#B2DFDB]']">
                          {{ cat.count }}
                      </span>
                    </button>
                  </li>
                 </ul>
              </div>
             </aside>

             <!-- Display results -->
              <div>
                <!-- Announces filter changes to screen readers -->
                 <p class="mb-4 min-h-4 text-sm dark:text-slate-400 text-[#B2DFDB]" aria-live="polite">
                  <template v-if="!projectsStore.loading && !projectsStore.error">
                    Showing {{ filtered.length }} {{ filtered.length === 1 ? 'project' : 'projects' }}
                    <template v-if="activeFilter !== All">in {{ activeLabel }}</template>
                  </template>
                 </p>

                 <!-- Error (only shows if store exposes `error`) -->
                  <div v-if="projectsStore.error" role="alert"
                    class="rounded-2xl border border-red-400/40 bg-red-500/10 p-6 text-sm text-red-200">
                    Projects could not be loaded. Check your connection and refresh the page.
                  </div>

                <!-- Loading: skeletons keep the layout from jumping -->
                 <div v-else-if="projectsStore.loading"
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div v-for="n in 6" :key="n"
                      class="h-72 animate-pulse rounded-2xl dark:bg-slate-900 bg-[#0F362F]" />
                  </div>

                <!-- Empty state -->
                 <div v-else-if="filtered.length === 0"
                    class="rounded-2xl border border-dashed p-10 text-center dark:border-slate-700 border-[#1A4A42]">
                    <p class="font-medium dark:text-white text-[#00F0A0]">No projects here yet</p>
                    <p class="mt-1 text-sm dark:text-slate-400 text-[#B2DFDB]">Try another category, or view all projects</p>
                    <button type="button" @click="activeFilter = All"
                      class="mt-4 rounded-xl px-4 py-2 text-sm font-medium transition-colors
                             dark:bg-indigo-600 dark:hover:bg-indigo-500 dark:text-white
                             bg-[#00F0A0] hover:bg-[#33F5B5] text-[#0B2B26]">
                        Show All Projects
                    </button>
                  </div>

              <!-- Grid: Three columns only from xl, because sidebar takes up space -->
               <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <ProjectCard
                  v-for="project in filtered"
                  :key="project.id"
                  :project="project"
                />
               </div>
              </div>
          </div>
      </div>
    </section>
  </PublicLayout>
</template>
