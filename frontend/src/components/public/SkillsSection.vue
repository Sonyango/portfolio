<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api//index.js';

const skillGroups = ref({})
const loading     = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/skills')
    skillGroups.value = data.data ?? {}
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section id="skills" class="py-24 px-4 dark:bg-slate-900/50 bg-[#0D3530]">
    <div class="max-w 6xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-sm font-semibold tracking-widest uppercase mb-3
                  dark:text-indigo-400 text-[#00F0A0]">
          What I Work With
        </p>
        <h2 class="font-display text-4xl font-bold dark:text-white text-[#00F0A0]">
          Skills & Technologies
        </h2>
      </div>

      <div v-if="loading" class="text-center dark:text-slate-400 text-[#B2DFDB]">Loading skills...</div>

      <div v-else-if="Object.keys(skillGroups).length === 0"
        class="text-center dark:text-slate-400 text-[#B2DFDB]">No skills added yet.
      </div>

      <div v-else class="space-y-10">
        <div v-for="(skills, category) in skillGroups" :key="category">
          <h3 class="text-sm font-semibold uppercase tracking-widest mb-4
                    dark:text-slate-400 text-[#7BB8B2]">
            {{ category }}
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="skill in skills"
              :key="skill.id"
              class="rounded-2xl p-4 transition-colors dark:bg-slate-900 dark:border-slate-800
                    dark:hover:border-indigo-500/30 bg-[#0B2B26] border-[#1A4A42] hover:border-[#00F0A0]/40">
              <div class="flex items-center justify-between mb-3">
                <span class="font-medium text-sm dark:text-white text-[#B2DFDB]">{{ skill.name }}</span>
                <span class="text-xs font-semibold dark:text-indigo-400 text-[#00F0A0]">
                  {{ skill.proficiency }}%
                </span>
              </div>
              <div class="w-full rounded-full h-1.5 dark:bg-slate-800 bg-[#1A4A42]">
                <div
                  class="h-1.5 rounded-full transition-all duration-700
                        dark:bg-linear-to-r dark:from-indigo-500 dark:to-violet-500 bg-[#00F0A0]"
                  :style="{ width: skill.proficiency + '%'}" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
