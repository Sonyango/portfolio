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
  <section id="experience" class="py-24 px-4 dark:bg-slate-950 bg-[#0B2B26]">
    <div class="max-w-4xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-sm font-semibold tracking-widest uppercase mb-3
                dark:text-indigo-400 text-[#00F0A0]">
          My Career Journey
        </p>
        <h2 class="font-display text-4xl font-bold dark:text-white text-[#00F0A0]">
          Work Experience
        </h2>
      </div>

      <div v-if="loading" class="text-center dark:text-slate-400 text-[#B2DFDB]">
        Loading work experiences...
      </div>

      <div v-else-if="experiences.length === 0"
        class="text-center dark:text-slate-400 text-[#B2DFDB]">
        No Experience added yet.
      </div>

      <!-- Timeline -->
       <div v-else class="relative">

        <!-- Vertical line -->
         <div class="absolute left-4 top-0 bottom-0 w-px dark:bg-slate-800 bg-[#1A4A42]" />
         <div class="space-y-8">
          <div v-for="exp in experiences" :key="exp.id" class="relative pl-12">

            <!-- Dot -->
             <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center
                      border-2 dark:bg-slate-900 dark:border-indigo-500 bg-[#0B2B26] border-[#00F0A0]">
              <div class="w-2 h-2 rounded-full dark:bg-indigo-500 bg-[#00F0A0]" />
             </div>

             <!-- Card -->
              <div class="rounded-2xl p-6 transition-colors dark:bg-slate-900 dark:border-slate-800
                        dark:hover:border-indigo-500/30 bg-[#0D3530] border-[#1A4A42] hover:border-[#00F0A0]/40">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">
                  <div>
                    <h3 class="font-semibold text-lg dark:text-white text-[#00F0A0]">{{ exp.role }}</h3>
                    <p class="font-medium dark:text-indigo-400 text-[#00F0A0]/80">{{ exp.company }}</p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-sm dark:text-slate-400 text-[#7BB8B2]">{{ exp.start_date }} - {{ exp.end_date }}</p>
                    <p v-if="exp.location" class="text-xs mt-0.5 dark:text-slate-500 text-[#7BB8B2]">
                      {{ exp.location }}
                    </p>
                  </div>
                </div>
                <p v-if="exp.description" class="text-sm leading-relaxed dark:text-slate-400 text-[#B2DFDB]">
                  {{ exp.description }}
                </p>
                <span class="inline-block mt-3 px-2.5 py-1 text-xs rounded-full font-medium dark:bg-green-500/10 dark:text-green-400
                             bg-[#00F0A0]/15 text-[#00F0A0] border dark:border-green-500/20 border-[#00F0A0]/30">
                  Current Role
                </span>
              </div>
          </div>
         </div>
       </div>
    </div>
  </section>
</template>
