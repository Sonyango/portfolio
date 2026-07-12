<script setup>
import { ref } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import ToastNotification from '@/components/admin/ToastNotification.vue';

const settingsStore = useSettingsStore()
const mobileOpen    = ref(false)

const navLinks = [
  { label: 'Home', to: '/' },
  { label: 'Projects',  to: '/projects' },
  { label: 'Blog',      to: '/blog' },
  { label: 'Contact',   to: '/contact' },
]
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <ToastNotification />
    <!-- Navbar -->
     <header class="fixed top-0 left-0 right-0 z-50 bg-slate-950/90 backdrop-blur-md border-b border-slate-800">
      <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">

        <!-- Logo -->
         <router-link to="/"
         class="font-display text-lg font-bold text-white hover:text-indigo-400
                 transition-colors">
          {{ settingsStore.get('site_name', 'Portfolio') }}
        </router-link>

        <!-- Desktop nav -->
         <nav class="hidden md:flex items-center ga-6">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="text-sm font-medium text-slate-400 hover:text-white
                   transition-colors"
            active-class="text-white">
            {{ link.label }}
          </router-link>

          <a
            :href="settingsStore.get('cv_url', '#')"
            target="_blank"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                   text-sm font-medium rounded-xl transition-colors"
          >
            Download CV
          </a>
         </nav>

         <!-- Mobile menu button -->
          <button
            @click="mobileOpen = !mobileOpen"
            class="md:hidden p-2 text-slate-400 hover:text-white">
            <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" />
            <XMarkIcon v-else class="h-6 w-6" />
          </button>
      </div>

      <!-- Mobile menu -->
       <div v-if="mobileOpen"
        class="md:hidden border-t border-slate-800 bg-slate-950 px-4 py-4
               space-y-2">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            @click="mobileOpen = false"
            class="block px-4 py-2.5 text-slate-300 hover:text-white
                 hover:bg-slate-800 rounded-xl text-sm font-medium
                 transition-colors">
            {{ link.label }}
          </router-link>

          <a
            :href="settingsStore.get('cv_url', '#')"
            target="_blank"
            class="block px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700
                 text-white rounded-xl text-sm font-medium transition-colors
                 text-center mt-2">
            Download CV
          </a>
      </div>
     </header>

     <!-- Page content -->
      <main class="pt-16">
        <slot />
      </main>

    <!-- Footer -->
     <footer class="border-t border-slate-800 py-10 mt-20">
      <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-slate-400 text-sm">
          &copy; {{ new Date().getFullYear() }}
          {{ settingsStore.get('site_name', 'Portfolio') }}
          All rights reserved.
        </p>
        <div class="flex items-center gap-4">
          <a
            v-if="settingsStore.get('github_url')"
            :href="settingsStore.get('github_url')"
            target="_blank"
            class="text-slate-400 hover:text-white text-sm transition-colors">
            GitHub
          </a>

          <a
            v-if="settingsStore.get('linkedIn_url')"
            :href="settingsStore.get('linkedIn_url')"
            target="_blank"
            class="text-slate-400 hover:text-white text-sm transition-colors">
            LinkedIn
          </a>

          <a
            v-if="settingsStore.get('twitter_url')"
            :href="settingsStore.get('twitter_url')"
            target="_blank"
            class="text-slate-400 hover:text-white text-sm transition-colors">
            Twitter / X
          </a>
        </div>
      </div>
     </footer>
  </div>
</template>
