<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index.js';

const experiences = ref([])
const loading     = ref(true)

onMounted(async () => {
  try{
    const { data } = await api.get('/experiences')
    experiences.value = data.data ?? []
  } finally{
    loading.value = false
  }
})
</script>

<template>
  <section id="experience" class="py-24 px-4 bg-white dark:bg-slate-950">
    <div class="max-w-4xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
          My Career Journey
        </p>
        <h2 class="font-display text-4xl font-bold text-white">
          Work Experience
        </h2>
      </div>

      <div v-if="loading" class="text-center text-slate-400">
        Loading work experiences...
      </div>

      <div v-else-if="experiences.length === 0"
        class="text-center text-slate-400">
        No Experience added yet.
      </div>

      <!-- Timeline -->
       <div v-else class="relative">

        <!-- Vertical line -->
         <div class="absolute left-4 top-0 bottom-0 w-px bg-slate-200 dark:bg-slate-800" />
         <div class="space-y-8">
          <div v-for="exp in experiences" :key="exp.id" class="relative pl-12">

            <!-- Dot -->
             <div class="absolute left-0 w-8 h-8 rounded-full bg-slate-900 border-2 border-indigo-500 flex items-center justify-center">
              <div class="w-2 h-2 rounded-full bg-indigo-500" />
             </div>

             <!-- Card -->
              <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 hover:border-indigo-500/30 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">
                  <div>
                    <h3 class="text-slate-900 dark:text-white font-semibold text-lg">{{ exp.role }}</h3>
                    <p class="text-indigo-400 font-medium">{{ exp.company }}</p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-slate-400 text-sm">{{ exp.start_date }} - {{ exp.end_date }}</p>
                    <p v-if="exp.location" class="text-slate-500 text-xs mt-0.5">
                      {{ exp.location }}
                    </p>
                  </div>
                </div>
                <p v-if="exp.description" class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                  {{ exp.description }}
                </p>
                <span class="inline-block mt-3 px-2.5 py-1 bg-green-500/10 text-green-400 text-xs rounded-full font-medium">
                  Current Role
                </span>
              </div>
          </div>
         </div>
       </div>
    </div>
  </section>
</template>
