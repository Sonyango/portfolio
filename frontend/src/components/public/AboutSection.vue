<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const settingsStore = useSettingsStore()
</script>

<template>
  <section id="about" class="py-24 px-4 dark:bg-slate-900/50 bg-[#0D3530]">
    <div class="max-w-6xl mx-auto">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

        <!-- Text -->
         <div>
          <p class="text-sm font-semibold tracking-widest uppercase mb-3
                    dark:text-indigo-400 text-[#00F0A0]">
            About Me
          </p>
          <h2 class="font-display text-4xl font-bold mb-6
                    dark:text-white text-[#00F0A0]">
            Passionate about building great software
          </h2>
          <div class="leading-relaxed space-y-4 dark:text-slate-400 text-[#B2DFDB]">
            <p v-for="(paragraph, i) in settingsStore
                  .get('about_text', 'I am a software developer.')
                  .split('\n').filter(p => p.trim())"
                :key="i">
                {{ paragraph }}
            </p>
          </div>

          <!-- Download CV -->
           <a v-if="settingsStore.get('cv_url')"
           :href="settingsStore.get('cv_url')"
           target="_blank"
           class="inline-flex items-center gap-2 mt-8 px-6 py-3 font-medium rounded-xl transition-colors text-sm
                  dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Download CV
            </a>
         </div>

        <!-- Stats / info cards -->
         <div class="grid grid-cols-2 gap-4">
          <div
            v-for="stat in [
              { label: 'Location', value: settingsStore.get('location', 'Nairobi, Kenya') },
              { label: 'Email', value: settingsStore.get('email', 'hello@example.com') },
              { label: 'Status', value: settingsStore.get('available_for_work') === 'true' ? 'Available' : 'Not Available' },
              { label: 'Focus',  value: 'Full Stack + ICT' },
            ]"
            :key="stat.label"
            class="rounded-2xl p-5 border dark:bg-slate-900 dark:border-slate-800 bg-[#0B2B26] border-[#1A4A42]" >
            <p class="text-xs font-medium uppercase tracking-wider mb-1 dark:text-slate-500 text-[#7BB8B2]">
              {{ stat.label }}
            </p>
            <p class="font-medium text-sm dark:text-white text-[#B2DFDB]">{{ stat.value }}</p>
        </div>
         </div>

      </div>
    </div>
  </section>
</template>
