<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const settingsStore = useSettingsStore()
</script>

<template>
  <section id="about" class="py-24 px-4 bg-slate-50 dark:bg-slate-900/50">
    <div class="max-w-6xl mx-auto">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

        <!-- Text -->
         <div>
          <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
            About Me
          </p>
          <h2 class="font-display text-4xl font-bold text-slate-900 dark:text-white mb-6">
            Passionate about building great software
          </h2>
          <div class="text-slate-600 dark:text-slate-400 leading-relaxed space-y-4">
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
           class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-indigo-600
                   hover:bg-indigo-700 text-white font-medium rounded-xl
                   transition-colors text-sm">
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
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5" >
            <p class="text-slate-500 text-xs font-medium uppercase tracking-wider mb-1">
              {{ stat.label }}
            </p>
            <p class="text-white font-medium text-sm">{{ stat.value }}</p>
        </div>
         </div>

      </div>
    </div>
  </section>
</template>
