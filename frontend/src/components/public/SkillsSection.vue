<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api//index.js';

const skillGroups = ref({})
const loading = ref(true)
const categories = ['Frontend', 'Backend', 'Database', 'ICT Services']

const getSkillsForCategory = (category) => Object.entries(skillGroups.value)
  .flatMap(([group, skills]) => group.trim().toLowerCase() === category.toLowerCase() ? skills : [])

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
  <section
    id="skills"
    class="rounded-2xl border border-[#1A4A42] bg-[#0D3530] p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
  >
    <header class="mb-4">
      <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#00F0A0] dark:text-indigo-400">
        What I work with
      </p>
      <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">
        Skills &amp; Tech Stack
      </h2>
      <p class="mt-2 text-sm leading-relaxed text-[#B2DFDB] dark:text-slate-400">
        A snapshot of my technical skills.
      </p>
    </header>

    <p
      v-if="loading"
      class="rounded-xl border border-[#1A4A42] bg-[#0B2B26] px-4 py-6 text-center text-sm text-[#B2DFDB] dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400"
      role="status"
      aria-live="polite"
    >
      Loading skills…
    </p>

    <p
      v-else-if="!categories.some((category) => getSkillsForCategory(category).length)"
      class="rounded-xl border border-[#1A4A42] bg-[#0B2B26] px-4 py-6 text-center text-sm text-[#B2DFDB] dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400"
    >
      No skills added yet.
    </p>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <article
        v-for="(category, index) in categories"
        :key="category"
        class="overflow-hidden rounded-xl border border-[#1A4A42] bg-[#0B2B26] transition-colors hover:border-[#00F0A0]/50 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-indigo-500/50"
      >
        <header class="flex items-center justify-between border-b border-[#1A4A42] px-4 py-4 dark:border-slate-800">
          <div class="flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-lg bg-[#00F0A0]/10 text-xs font-bold text-[#00F0A0] dark:bg-indigo-500/10 dark:text-indigo-400">
              {{ String(index + 1).padStart(2, '0') }}
            </span>
            <h3 class="font-semibold text-white">{{ category }}</h3>
          </div>

          <span class="text-xs text-[#7BB8B2] dark:text-slate-500">
            {{ getSkillsForCategory(category).length }}
            {{ getSkillsForCategory(category).length === 1 ? 'skill' : 'skills' }}
          </span>
        </header>

        <div class="overflow-x-auto px-4 py-2">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider text-[#7BB8B2] dark:text-slate-500">
                <th scope="col" class="py-2 pr-3 font-medium">Skill</th>
                <th scope="col" class="py-2 font-medium">Proficiency</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-[#1A4A42] dark:divide-slate-800">
              <tr v-for="skill in getSkillsForCategory(category)" :key="skill.id">
                <th scope="row" class="py-3 pr-3 text-sm font-medium text-[#B2DFDB] dark:text-slate-200">
                  {{ skill.name }}
                </th>
                <td class="py-3">
                  <div class="flex min-w-28 items-center gap-3">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#1A4A42] dark:bg-slate-800">
                      <div
                        class="h-full rounded-full bg-[#00F0A0] transition-[width] duration-500 dark:bg-indigo-500"
                        :style="{ width: skill.proficiency + '%' }"
                        role="progressbar"
                        :aria-label="`${skill.name} proficiency`"
                        :aria-valuenow="skill.proficiency"
                        aria-valuemin="0"
                        aria-valuemax="100"
                      />
                    </div>
                    <span class="w-10 text-right text-xs font-semibold text-[#00F0A0] dark:text-indigo-400">
                      {{ skill.proficiency }}%
                    </span>
                  </div>
                </td>
              </tr>

              <tr v-if="!getSkillsForCategory(category).length">
                <td colspan="2" class="py-4 text-sm text-[#7BB8B2] dark:text-slate-500">
                  No skills listed yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>

