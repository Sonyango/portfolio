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
  <section id="skills" class="py-24 px-4 bg-slate-50 dark:bg-slate-900/50">
    <div class="max-w 6xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
          What I Work With
        </p>
        <h2 class="font-display text-4xl font-bold text-white">
          Skills & Technologies
        </h2>
      </div>

      <div v-if="loading" class="text-center text-slate-400">Loading skills...</div>

      <div v-else-if="Object.keys(skillGroups).length === 0"
        class="text-center text-slate-400">No skills added yet.
      </div>

      <div v-else class="space-y-10">
        <div v-for="(skills, category) in skillGroups" :key="category">
          <h3 class="text-slate-400 text-sm font-semibold uppercase tracking-widest mb-4">
            {{ category }}
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="skill in skills"
              :key="skill.id"
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4
                     hover:border-indigo-500/30 transition-colors">
              <div class="flex items-center justify-between mb-3">
                <span class="text-slate-900 dark:text-white font-medium text-sm">{{ skill.name }}</span>
                <span class="text-indigo-400 text-xs font-semibold">
                  {{ skill.proficiency }}%
                </span>
              </div>
              <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5">
                <div
                  class="bg-linear-to-r from-indigo-500 to-violet-500
                         h-1.5 rounded-full transition-all duration-700"
                  :style="{ width: skill.proficiency + '%'}" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
