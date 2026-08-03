<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownIcon } from '@heroicons/vue/24/outline';

const settingsStore = useSettingsStore()
</script>

<template>
  <section class="min-h-screen w-full flex items-center justify-center px-4 relative overflow-hidden
                  dark:bg-slate-950 bg-[#0B2B26]">

    <!-- Background glow-->
     <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-72 h-72
                dark:bg-indigo-600/10 bg-[#00F0A0]/5 rounded-full blur-3xl" />
     </div>

     <div v-if="settingsStore.loading"
      class="text-[#7BB8B2] dark:text-slate-500 text-sm animate-pulse">
      Loading...
     </div>

     <div v-else class="max-w-4xl mx-auto text-center relative z-10">

      <!-- Availability badge-->
       <div v-if="settingsStore.get('available_for_work') === true"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium mb-8 border
                dark:bg-green-500/10 dark:border-green-500/20 dark:text-green-400 bg-[#00F0A0]/10 border-[#00F0A0]/30 text-[#00F0A0]">
          <span class="w-2 h-2 rounded-full animate-pulse dark:bg-green-400 bg-[#00F0A0]" >
            Available for work
          </span>
        </div>

      <!-- Main heading -->
       <h1 class="font-display text-5xl md:text-7xl font-bold mb-6 leading-tight dark:text-white text-[#00F0A0]">
        {{ settingsStore.get('hero_title', 'Hi, I\'m a Full Stack Developer') }}
       </h1>

       <!-- Subtitle -->
        <p class="text-xl mb-10 max-w-2xl mx-auto leading-relaxed dark:text-slate-400 text-[#B2DFDB]">
          {{ settingsStore.get('hero_subtitle',
          'I build modern web applications and ICT solutions.') }}
        </p>

        <!-- CTA Buttons -->
         <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <router-link to="/projects"
            class="px-8 py-3.5 font-medium rounded-xl transition-colors text-sm dark:bg-indigo-600
                  dark:hover:bg-indigo-700 dark:text-white bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
            View My Work
          </router-link>
          <router-link to="/contact"
            class="px-8 py-3.5 font-medium rounded-xl transition-colors text-sm border dark:border-slate-700 dark:hover:border-slate-500
                   dark:text-slate-300 dark:hover:text-white border-white/30 hover:border-white text-white hover:text-white">
            Get In Touch
          </router-link>
         </div>

         <!-- Social links-->
          <div class="flex items-center justify-center gap-4 mt-10">
            <a
              v-if="settingsStore.get('github_url')"
              :href="settingsStore.get('github_url')"
              target="_blank"
              class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                    text-[#B2DFDB] hover:text-[#00F0A0]">
              GitHub
            </a>
            <span class="dark:text-slate-700 text-[#1A4A42]">.</span>

            <a
              v-if="settingsStore.get('linkedin_url')"
              :href="settingsStore.get('linked_url')"
              target="_blank"
              class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                    text-[#B2DFDB] hover:text-[#00F0A0]">
              LinkedIn
            </a>
            <span class="dark:text-slate-700 text-[#1A4A42]">.</span>

            <a
              v-if="settingsStore.get('email')"
              :href="settingsStore.get('email')"
              class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                    text-[#B2DFDB] hover:text-[#00F0A0]">
              Email
            </a>
          </div>
     </div>

     <!-- Scroll indicator -->
      <div class="absolute bottom-8 left-1/2 -translate-x-1/2
                animate-bounce dark:text-slate-600 text-[#1A4A42]">
        <ArrowDownIcon class="w-5 h-5" />
      </div>
  </section>
</template>
