<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownIcon } from '@heroicons/vue/24/outline';

const settingsStore = useSettingsStore()
</script>

<template>
  <section class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden">

    <!-- Background glow-->
     <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96
                  bg-indigo-600/10 rounded-full blur-3xl" />
     </div>

     <div class="max-w-4xl mx-auto text-center relative z-10">

      <!-- Availability badge-->
       <div v-if="settingsStore.get('available_for_work') === true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/10
                  border border-green-500/20 rounded-full text-green-400 text-sm font-medium mb-8">
          <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse" >
            Available for work
          </span>
        </div>

      <!-- Main heading -->
       <h1 class="font-display text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
        {{ settingsStore.get('hero_title', 'Hi, I\'m a Full Stack Developer') }}
       </h1>

       <!-- Subtitle -->
        <p class="text-xl text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
          {{ settingsStore.get('hero_subtitle',
          'I build modern web applications and ICT solutions.') }}
        </p>

        <!-- CTA Buttons -->
         <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <router-link to="/projects"
            class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white
                 font-medium rounded-xl transition-colors text-sm">
            View My Work
          </router-link>
          <router-link to="/contact"
            class="px-8 py-3.5 border border-slate-700 hover:border-slate-500
                 text-slate-300 hover:text-white font-medium rounded-xl
                 transition-colors text-sm">
            Get In Touch
          </router-link>
         </div>

         <!-- Social links-->
          <div class="flex items-center justify-center gap-4 mt-10">
            <a
              v-if="settingsStore.get('github_url')"
              :href="settingsStore.get('github_url')"
              target="_blank"
              class="text-slate-400 hover:text-white text-sm transition-colors">
              GitHub
            </a>
            <span class="text-slate-700">.</span>

            <a
              v-if="settingsStore.get('linkedin_url')"
              :href="settingsStore.get('linked_url')"
              target="_blank"
              class="text-slate-400 hover:text-white text-sm transition-colors">
              LinkedIn
            </a>
            <span class="text-slate-700">.</span>

            <a
              v-if="settingsStore.get('email')"
              :href="settingsStore.get('email')"
              class="text-slate-400 hover:text-white text-sm transition-colors">
              Email
            </a>
          </div>
     </div>

     <!-- Scroll indicator -->
      <div class="absolute bottom-8 left-1/2 -translate-x-1/2
                animate-bounce text-slate-600">
        <ArrowDownIcon class="w-5 h-5" />
      </div>
  </section>
</template>
