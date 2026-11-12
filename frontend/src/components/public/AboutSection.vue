<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import SkillsSection from '@/components/public/SkillsSection.vue';

const settingsStore = useSettingsStore()
</script>

<template>
  <section id="about" class="py-10 px-4 dark:bg-slate-900 bg-[#0D3530]">
    <div class="mx-auto w-full">

      <!-- Centered section label -->
       <p class="text-center mb-6 text-sm font-semibold tracking-widest uppercase dark:text-indigo-400 text-[#00F0A0]">
         About
       </p>

       <!-- Left and right columns -->
      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">

        <!-- Left: About text card -->
        <div class="rounded-2xl border p-6 dark:bg-slate-950 dark:border-slate-800 bg-[#0B2B26] border-[#1A4A42]">
          <h2 class="font-display text-4xl font-bold mb-3
                    dark:text-white text-[#00F0A0]">
            Passionate about building great software
          </h2>

          <div class="leading-relaxed space-y-4 dark:text-slate-400 text-[#B2DFDB]">
            <p v-for="(paragraph, i) in settingsStore
              .get('about_text', 'I am a software developer.')
              .split('\n').filter(p => p.trim())" :key="i">
              {{ paragraph }}
            </p>
          </div>

        </div>

        <!-- Right: Stats / info cards above skills card -->
         <div class="flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 rounded-2xl p-3 dark:bg-slate-950 dark:border-slate-800 bg-[#0B2B26] border-[#1A4A42]">
              <div v-for="stat in [
                { label: 'Location', value: settingsStore.get('location', 'Nairobi, Kenya') },
                { label: 'Email', value: settingsStore.get('email', 'hello@example.com') },
                { label: 'Status', value: settingsStore.get('available_for_work') === 'true' ? 'Available for work' : 'Not Available' },
                { label: 'Focus', value: 'Software Development, ICT Services' },
              ]" :key="stat.label"
                class="rounded-2xl p-5 border min-w-0 dark:bg-slate-900 dark:border-slate-800 bg-[#0B2B26] border-[#1A4A42]">
                <p class="text-xs font-medium uppercase tracking-wider mb-1 dark:text-slate-500 text-[#7BB8B2]">
                  {{ stat.label }}
                </p>
                <p class="font-medium text-sm min-w-0 break-all dark:text-white text-[#B2DFDB]">{{ stat.value }}</p>
              </div>
            </div>

            <div class="rounded-2xl border p-2 dark:bg-slate-950 dark:border-slate-800 bg-[#0B2B26] border-[#1A4A42]">
              <SkillsSection />
            </div>
        </div>

      </div>
    </div>
  </section>
</template>
